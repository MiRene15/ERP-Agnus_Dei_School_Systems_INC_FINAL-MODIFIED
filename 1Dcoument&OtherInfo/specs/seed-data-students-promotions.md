# Spec: Seed Data Students Promotions

- **Status**: Implemented
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-12 (fix addendum confirmed — "confirm and do exec-spec")

## 1. Why We Need This
Class lists and demographics look thin because students only exist for the current year with no promotion story. This fills believable students for 2024-2025, 2025-2026, and current year — with promotions carried forward — plus early birds for 2027-2028, so every class list and chart reads full.

## 2. Who Is Affected
* **Registrar (primary)** — admissions, enrollments, and promotions read true per year.
* **Principal / Directress** — demographics and honors read alive per year.
* **Cashier** — every enrollment has a ledger to bill against (money itself is Child 3).
* **Teachers** — class lists full per year to grade against (grades are Child 4).
* **IT Admin** — runs once per dataset; re-runs duplicate nothing.

## 3. Business Flow: Today vs After
- **As-is**: Students built for the active year only (15 per section target). Other years blank.
- **To-be**:
  1. 2024-2025: full cohorts, 12 per section, admission dates scattered June–August 2024.
  2. 2025-2026: promotion carry-forward one grade up + fresh Kinder/Grade 11 + small transferee mix, scattered dates.
  3. Current: full cohorts, 15 per section, new + promoted mix, scattered to today.
  4. 2027-2028: early-bird Kinder/Grade 11 only (applied, not yet billed or graded).
- **Preserved**: One enrollment per student per year. Registrar-corrected genders never overwritten. Audit keeps who/when per year. Saint sections from Child 1.
- **Exceptions**: Double-enrollment attempts keep the earliest; promoted students without a matching next-grade section land least-loaded; locked years refuse new admissions plainly.

## 4. How It Should Work
1. IT Admin runs starter data (after Child 1 backbone) → students with admissions + enrollments + ledgers build per year with scattered dates.
2. A visible sample cohort promotes forward (example: Grade 7 in 2024 → Grade 8 in 2025 → Grade 9 current).
3. Honors and scholars flagged every year for showcases.
4. Registrar opens any class list or demographics view → switches year → sees full lists and mixed charts; 2027-2028 shows the small early-bird list.
5. Re-run → nothing duplicates; corrected genders kept.
6. Fix addendum 2026-10-12: when the seeder needs a new student number, it reads the highest number already used for that year and adds one — it never counts rows by date.
7. Fix addendum 2026-10-12: if two runs race and pick the same number, the seeder retries with a fresh number (up to 5 tries) instead of aborting the whole run.

## 5. Look & Feel (UX)
- Where it lives: no new screen. Class lists (primary check), demographics, honors badges where they already appear.
- Key states: default (current pre-selected, full 15/section); history (2024/2025 full 12/section, locked); ready (2027-2028 early birds only + plain "enrollment not yet open" note); permission-denied (locked-year admission refused plainly).
- Plain language, fewest clicks (year picker only). Charts never flat — per-section gender skew + religion/barangay mix.

## 6. Business Rules
### Must always be true
- One enrollment per student per year; promoted students land one grade higher next year.
- Volumes: 12 per section prior years, 15 per section current, small early-bird group 2027-2028.
- Every enrollment sits in an active Child-1 section of the same year and joins its class list.
- Registrar-corrected genders kept; blanks filled 47/47/3/3 (Male/Female/Non-binary/Prefer not to say).
- Honors/scholars appear every historical year; 12-digit LRN; local addresses; weekday-scattered dates in window.
- Fix addendum 2026-10-12: student numbers are never reused, even when dates are scattered into past years or rows are deleted.
- Fix addendum 2026-10-12: a re-run never aborts the whole batch on a single number clash — it retries that one student.

### Must never happen
- A student has two active enrollments the same year.
- An enrollment points at a missing section or wrong year.
- A locked year accepts new admissions or enrollments.
- 2027-2028 early birds carry payments or grades.
- Virtue section names appear in any year.
- Fix addendum 2026-10-12: the generator counts rows by creation date to pick the next number.
- Fix addendum 2026-10-12: a partial crashed run blocks the next run (resume, don't wipe).

### Edge cases and what happens then
- Double-enroll same year → keeps earliest, others ignored.
- No matching next-grade section → least-loaded section of that grade that year.
- Re-run with corrected gender → keeps registrar value, fills blanks only.
- Early-bird re-run → same kids, no duplicates, stays unbilled/ungraded.

## 7. Out of Scope
- Fee amounts, payments, receipts, ledgers detail (Child 3 — this child only creates the ledger shell to bill against).
- Grades, attendance, library, clinic (Child 4).
- Backbone sections/classes/fees (Child 1 — 2027-2028 fee tables already live there).
- New enrollment screens; transferee-surge, sibling-showcase, honor-roll snapshots (noted follow-ups).
- Fix addendum 2026-10-12 out of scope: changing the number format (`YYYY-XXXXX` stays); backfilling or renumbering old numbers; touching locked-year enforcement or `migrate:fresh`.

## 8. Success Checks
- [ ] Every active section × 2024/2025/current opens a full class list (12/12/15).
- [ ] Demographics per year show mix (gender, religion, barangay non-flat).
- [ ] Sample cohort traceable G7→G8→G9 across years.
- [ ] Honors/scholars visible every historical year.
- [ ] 2027-2028 shows early-bird list only, nothing billed or graded.
- [ ] Re-run duplicates nothing and keeps corrected genders.
- [ ] Fix addendum 2026-10-12: re-running `StudentScatterSeeder` finishes with no duplicate-number error.
- [ ] Fix addendum 2026-10-12: no duplicate value exists in the student-number column.

## 9. Open Questions (if any)
None. (Parent volume question resolved here: 15/12/early-bird.)

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: class lists, enrollment queues, demographics, honors views, year pickers (no layout change).
- Likely areas of the codebase (from code inspection):
  - `Actual_Website/ERP_Agnus_Dei_School_Systems_INC/database/seeders/StudentScatterSeeder.php:21-23,43-50,72-200` — `TARGET_PER_SECTION`, `BASELINE_STUDENT_ID`, year loop per `school_year`; keep `updateOrCreate(email)` + `updateOrCreate(student+year)` enrollment keys; keep gender rule (`student-gender-data-quality.md` §6); scatter `created_at` in year windows; strand from section prefix.
  - `.../seeders/StudentsAndFeesSeeder.php:39-...` — fixed honor/scholar seeds stay current-year showcases; history uses scattered honors flags.
  - `.../seeders/StudentLinkedInfoSeeder.php` — guardian/emergency/previous-school variety per year.
  - Depends on Child 1 sections/classes + fee shells; feeds Child 3 (ledgers→payments) and Child 4 (enrollment→grades).
- Data/records touched: users (role 7), students, admissions, enrollments (+`enrollment_subject` pivots), ledger shells — each stamped to its school year.
- Fix addendum 2026-10-12 cause: `Student::generateStudentNumber()` used `whereYear(created_at)->count()+1`, but the seeder backdates `created_at` into 2024/2025 windows, so the count undercounts and re-issues e.g. `2026-00480`.
- Fix addendum 2026-10-12 pointers: `app/Models/Student.php:59-75` changes to `MAX(student_number)` suffix +1 per year (keep `pg_advisory_xact_lock` guard); `database/seeders/StudentScatterSeeder.php:647-649` wraps number pick + create in retry loop catching unique violation (max ~5); check same count pattern in `Admission` boot + `StudentsAndFeesSeeder.php:236` read-only.
- Parent: `seed-data-connected-history.md` (Approved). Previous: `seed-data-foundations.md` (Approved). Next: `seed-data-payments-fulfilled.md`.
- Roles/permissions involved: none changed.

## 11. Approval
> Approved by user on 2026-10-11 (original). Re-approved by user on 2026-10-12 for fix addendum ("confirm and do exec-spec").

## 12. Implementation Note
- Implemented 2026-10-11: multi-year `StudentScatterSeeder` (12/12/15 + Kinder/Grade 11 early birds, +1-grade promotions chained via `promoted_to_enrollment_id`, per-year ledger shells) + `StudentsAndFeesSeeder` per-year ledger key. Marked Implemented per user request; seed + Success-Check verification commands provided but not yet run back — re-run checks before Child 3.
- Fix addendum implemented 2026-10-12: `Student::generateStudentNumber()` MAX-suffix +1 (keeps `pg_advisory_xact_lock`); `StudentScatterSeeder::createFreshStudent()` retries single-student transaction up to 5x on `students_student_number_unique`/`23505`. Marked Implemented per user direction ("mark implemented"); seed re-run + duplicate-SQL + class-list checks NOT yet confirmed — run them before relying on this.
