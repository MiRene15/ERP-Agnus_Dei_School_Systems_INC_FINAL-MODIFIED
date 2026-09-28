# System Verification + Scheduling Fix Batch — 2026-09-25 (Session 53)

## Scope

User request: **"check everything especially the scheduling function."**
Two read-only audits were run (scheduling function audit + verification of the
recent sessions 49–52 changes), then all confirmed findings were fixed in one batch.

---

## A. Scheduling audit — findings

### Critical (live DB + code)

1. **75 section-level double-bookings (live).** The seeder's `resolveAssignment()`
   only tracked *teacher* availability — never the section's. Two subjects of the same
   section can land on the same day/slot. The principal timetable shows them (two rows
   same cell), but the **student schedule UI hides them** (it renders per time-slot key),
   so students see a timetable that silently drops conflicting classes.
2. **SHS classes missing (live).** `$seniorHighPlans` hardcodes section names
   (`'GAS - St. Benedict'` etc.). The live `sections` table names drifted (rename-by-index
   in `SubjectsAndSectionsSeeder`), so sections like Grade 12 GAS have **0 classes** →
   **3 students have no classes and no schedule**; class **#209 has no schedule**.
3. **310 room double-bookings (live).** Room numbering restarts **per grade**
   (`$sectionIndex + 101` inside each `$sectionsByGrade` group) → Grade 1-A and Grade 2-A
   both get `E-101` and collide whenever their slots coincide.

### High (code)

4. **Conflict checks not scoped by school year/status** (`schedulesStore`,
   `schedulesUpdate`, `schedulesImport`): they match *all* schedules ever created —
   an old-year or archived class can block a valid new slot.
5. **Grade tabs snap back to Grade 7** (`schedules.blade.php`; same pattern in
   `grades.blade.php`): the grade pills are plain links (`?grade_level=…`) but
   `ajaxTable`'s `init()` immediately reloads **without** `grade_level` in its filters →
   the controller default (`Grade 7` / Kinder) wins on every AJAX round-trip.
6. **Validation errors invisible.** Neither `schedules-manage` nor `schedules-edit`
   renders `$errors` — a failed PATCH redirects back with no visible message.
7. **"Edit Existing" tab broken 3 ways:** (a) it fetches
   `/principal/schedules?class_id=…&ajax=1` but the controller ignores `class_id`
   (returns Grade 7 regardless); (b) the generated inline PATCH form sends only
   `room` — validation requires `day_of_week/start_time/end_time` → always fails;
   (c) the failure is invisible because of #6.
8. **API 500s:** `ApiController::teacherClasses()` eager-loads `section` on `Classes`
   (relation doesn't exist — `section` is a string column) and `principalSchedules()`
   eager-loads `teacher/section/subject` on `Schedule` (only `schoolClass()` exists) →
   `RelationNotFoundException`. `teacherClassShow()` has the same bug.
9. **CSV import rejects Excel-saved files:** a UTF-8 BOM on the first header cell makes
   header comparison fail → "Invalid CSV header" for a correct file.
10. **Registrar section update has no duplicate guard / grade whitelist**
    (`SectionController::update`) — `store()` checks duplicates, `update()` doesn't.

### Medium / low

11. **`SHS` phantom grade tab** in the schedule/grades/manage grade lists — no class has
    `grade_level = 'SHS'`, the tab always renders empty.
12. **Seeder re-run latent issues:** `term` `''` vs `NULL` key drift (would duplicate
    classes), classes orphaned by section renames stay `active`, and the
    "ensure every class has a schedule" fallback forces **Monday 07:00** with no
    conflict check (injected conflicts).
13. *Accepted cosmetics:* 24-hour-only time display, Monday–Friday only (no Saturday
    classes by design).

---

## B. Recent-changes verification — findings

1. **Library charts in the directress hub never render.** `library-reports-results.blade.php`
   injects `<canvas>` + `<script src=chart.js>` + inline `new Chart(...)` through
   `ajaxTable`'s `x-html` — **Alpine's `x-html` does not execute injected `<script>` tags**
   (same known issue that removed the cashier collections chart in session 49).
2. **Clinic logs / library visits duplicate on re-runs across days:** their
   `updateOrCreate` keys include `now()`-derived dates (`incident_date`, `time_in`) →
   a re-run on a later day inserts a *second* copy of every row.
3. **Overdue library rows can have `due < borrow`:** currently-borrowed overdue rows use
   `return_date = now() - (1 + id%10)` while `borrow_date = now() - (1 + …%60)`; when the
   borrow is recent and the due offset large, the due date lands *before* the borrow date.
4. All other checked items passed: 5 report partials' variables match controller passes;
   `$activeTab` correctly forwarded on all 6 `?tab=` endpoints; legacy routes redirect;
   export buttons/CSV content/audit logs verified; seeder re-run idempotency verified
   (except #2 above).

---

## C. Fixes applied (this batch)

### Code — scheduling

| # | File | Fix |
|---|------|-----|
| 4 | `PrincipalController` | New `findScheduleConflict()` helper used by store/update/import: checks **class / section (grade+section+year) / teacher / room** overlaps, all scoped to `school_year` + `status = active` (excludes archived/old-year rows) |
| 5 | `schedules.blade.php`, `grades.blade.php`, `PrincipalController` | `grade_level` added to `ajaxTable` initial filters; grade pills converted from plain links to Alpine `filters.grade_level = …; reload()` → tabs no longer snap back; `SHS` phantom entry removed from all three grade lists |
| 6 | `schedules-manage.blade.php`, `schedules-edit.blade.php` | `$errors->any()` block rendered under the session flashes |
| 7 | `schedules-manage.blade.php` | **Edit Existing tab rebuilt client-side:** loads `schedules` on `Classes` and renders each class's slots directly from the payload (no fetch, no `class_id` gap); inline form now sends **day + start + end + room** prefilled → passes validation; Delete with confirm; "Open" link to the full edit page kept |
| 8 | `ApiController` | `teacherClasses/teacherClassShow` → `with('subject')` (drop phantom `section` relation); `principalSchedules` → `with('schoolClass.subject', 'schoolClass.teacher')` |
| 9 | `PrincipalController::schedulesImport` | Strips UTF-8 BOM from the first header cell before header comparison |
| 10 | `SectionController::update` | Duplicate-name guard (excluding self) + `grade_level` `in:` whitelist on store/update |

### Seeders

| File | Fix |
|------|-----|
| `TeachersClassesSchedulesSeeder` | **(a)** SHS plans built from the **DB** `sections` table (strand parsed from `section_name`: STEM/ABM/HUMSS/GAS) instead of hardcoded names → live name drift can no longer zero out a section. **(b)** Rooms numbered **globally per department** (one unique room per section: `E/J/K/S-1xx`) instead of restarting per grade → room conflicts impossible by construction. **(c)** `resolveAssignment()` now reserves **section slots** as well as teacher slots (and room), scanning all 8 slots × 5 day-patterns before falling back. **(d)** Pre-loads existing DB schedules (teacher/section/room) for classes *not* covered by plans, so re-runs can't collide with untouched schedules. **(e)** "Every class needs a schedule" fallback now **searches for a conflict-free slot** (DB-checked, active+year scoped) instead of forcing Monday 07:00; skipped with a warning if none free. **(f)** Normalization pass: `term ''` → `NULL`; current-year classes whose `(grade_level, section)` pair isn't in the active `sections` table are **deactivated** (kills rename-drift orphans instead of duplicating), their `enrollment_subject` rows and schedules removed. |
| `LibraryAndClinicSeeder` | **(a)** Library transactions/visits/clinic logs now **skip if the seeded row already exists** (key without the date) → re-runs on any later day no longer duplicate or churn dates, and never touch rows created by the real librarian/nurse UI. **(b)** Overdue loans: borrow forced to ≥ 3 days back and due clamped to `max(now − offset, borrow + 1 day)` → **`due ≥ borrow` always**, and still `< now` (overdue stays overdue). |

### Verification-report fixes

| Item | Fix |
|------|-----|
| Library charts dead under `x-html` | Chart.js canvas + `<script>` block removed from `library-reports-results.blade.php`; replaced with a **pure-CSS stacked availability bar** (Available / Borrowed / Overdue with counts) — renders 100% inside `x-html`, light + dark |

### Live repair

New command **`php artisan schedules:repair`** (`app/Console/Commands/RepairSchedules.php`):

1. prints a **before-report** (section/teacher/room conflicts, classes without
   schedules, active sections without classes, enrollments without class links);
2. runs the fixed `TeachersClassesSchedulesSeeder` (idempotent `updateOrCreate`):
   creates the missing SHS classes + schedules, deactivates rename-drift orphans,
   rewrites rooms to be section-unique, re-resolves all slots **section-aware**;
3. backfills `enrollment_subject` for active enrollments missing links;
4. inserts **missing-only** assessments/grades for the newly linked
   enrollment×class pairs (existing grades untouched — `GradesAssessmentsSeeder`
   is intentionally *not* re-run because its scores are random);
5. prints the **after-report** for verification.

Run against the live Supabase DB in this session (result numbers appended below).

---

## D. Verification & live results

- `php -l` on every touched PHP file — clean.
- `php artisan view:cache` — all blades compile; `view:clear` after.
- `php artisan route:list --name=schedules` — 8 routes intact; `schedules:repair` registered.
- **Live repair #1 (`schedules:repair` against Supabase):**

  | Metric | Before | After |
  |---|---|---|
  | Section overlaps | **75** | **0** |
  | Room overlaps | **310** | **0** |
  | Teacher overlaps | 0 | 0 |
  | Active classes w/o schedule | 1 | 0 |
  | Active sections w/o classes | 1 | 0 |
  | Active enrollments w/o class links | 2 | 0 |

  Plus: 1,039 enrollment class links ensured, 12,396 assessments + 3,099 grades created for
  the rebuilt class pairs (missing-only; existing grades untouched).

- **Incident found & fixed during the live run:** the run reported "Deactivated 209 orphaned
  classes" — i.e. the legacy generic→saint section rename loop had renamed every class
  (`A` → `St. Agnes`, …) while the live `sections` table still uses **generic names**
  (`A`, `B`, `STEM-A`, …), so the orphan guard saw every class as drifted. The classes were
  rebuilt under the correct (generic) names — but the old rows kept their grades, which would
  have shown duplicate subject grades per student. **Two fixes followed:**
  1. Seeder: the legacy rename now only runs when the `sections` table actually holds the
     target saint names and *not* the old ones (rename is skipped on generic-name DBs).
  2. Seeder: orphaned classes are now **deleted** (FK cascade clears grades/assessments/
     schedules/links) instead of deactivated, so dead grade rows can never linger.
  3. Live cleanup: removed the 209 dead classes → cascade removed their 3,066 grades +
     12,264 assessments. Final state: **215 classes, 3,099 grades, 12,396 assessments,
     0 rows referencing missing classes.**
- **Live repair #2 (idempotency proof):** re-ran `schedules:repair` → `0` orphans removed,
  `0` links inserted, `0` grades created, all six metrics at **0** before *and* after.

