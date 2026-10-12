# Spec: Seed Data Grades Attendance Support

- **Status**: Implemented
- **Created**: 2026-10-11
- **Revised**: 2026-10-12 — tighten volumes & shapes (assessments, pending, fails, library, clinic, attendance)
- **Approved by**: user on 2026-10-12
- **Implemented**: 2026-10-12 — Slices 1–4 implemented (GradesAssessmentsSeeder multi-year, AttendanceHistorySeeder new + DatabaseSeeder registration, LibraryAndClinicSeeder per-year seasonal). Acceptance checks NOT run — no verification output was provided; closed at the user's explicit direction without confirmed passes. Re-run the Phase 5 checks before relying on this in production.

## 1. Why We Need This
Report cards, gradebooks, attendance sheets, and library/clinic hubs look empty for history because they only exist for the current year. This fills believable academic and support history for 2024-2025 and 2025-2026 plus a live current year — so every sheet, card, and trend demos full without slowing the starter-data run.

## 2. Who Is Affected
* **Teachers (primary)** — grades and attendance per class per year.
* **Librarian** — borrows, returns, and fines per year.
* **Nurse / Clinic** — visit trends per year.
* **Principal / Registrar** — report cards, completion flags, and trends per year.
* **Parents / Students (read-only)** — cards and borrowing history read complete.
* **IT Admin** — runs once; re-runs never re-randomize.

## 3. Business Flow: Today vs After
- **As-is**: Assessments, grades, attendance, library, and clinic built for the active year only with today's dates.
- **To-be**:
  1. 2024-2025: 12 assessments per class (4 types × 3 terms) → fully graded → attendance ~95% present → ~30% of students borrow once (15% of those late with fine) → clinic ~10 visits/month seasonal → locked.
  2. 2025-2026: same → locked, honors recomputed.
  3. Current: Terms 1-2 full (8 assessments per class); Term 3 partial (2 of 4 types built, ~30% of those grades still pending) + ~10% of loans still open + recent clinic visits → unlocked.
  4. 2027-2028: zero academic/support rows.
- **Preserved**: Grade scale, term weights, and lock rules unchanged. Health data stays role-gated. Audit keeps who/when per row.
- **Exceptions**: Orphan enrollments link to twin classes before grading; open loans keep due dates on re-run; locked years refuse new rows plainly.

## 4. How It Should Work
1. IT Admin runs starter data (after Children 1–3) → per-year assessments, grades, attendance, library transactions, clinic visits build with scattered weekday dates inside each school-year window (June–March).
2. Teachers open any gradebook/attendance sheet → switch year → see full sheets; principal prints sample report cards per year with honors where earned.
3. Librarian/nurse open hubs → switch year → see returns/fines and seasonal visit trends, non-flat.
4. Re-run → existing grades kept, nothing re-randomized, no duplicates.

## 5. Look & Feel (UX)
- Where it lives: no new screen. Gradebooks, report cards, attendance sheets, library/clinic hubs. Primary check: year switch shows full sheets + printable cards + trends.
- Key states: default (current pre-selected, Terms 1-2 complete + Term 3 partly pending); history (complete, locked); ready (2027-2028 zero rows, plain note); error (locked-year write refused plainly); permission-denied (health/grade roles unchanged).
- Plain language, fewest clicks. Honors badges where they already appear; trends seasonal, not flat.

## 6. Business Rules
### Must always be true
- Every row joins a real class + enrollment of the same school year.
- History years (2024-2025, 2025-2026): 12 assessments per class — Written Work / Quiz / Seatwork / Exam × 1st, 2nd, 3rd Term — each with a matching grade. Current year: 8 complete (Terms 1-2) + 2 built in Term 3.
- Current Term 3: ~30% of built grades left pending (scattered per class, never a whole class blank); Terms 1-2 fully graded.
- Exactly 2 showcase students school-wide carry 1 failing mark each in history only; every other mark is complete — proves fail display with no gaps.
- Library per year: ~30% of students borrow once; ~15% of those loans return late with a per-day fine; book counts always match open loans. Current year keeps ~10% of loans open (borrowed, due future/overdue).
- Clinic per year: ~10 visits/month average — ~15 in flu-peak months (Jun-Jul, Sep-Jan: colds/flu), ~6 in off months (check-up/headache/minor injury), 1–2 referrals per term. Dates scattered weekdays in window.
- Attendance per year: ~95 present of every 100 marks, rest absent/late scattered; current Term 3 ~70% built (taking still ongoing).
- Honors computed from grades every history year.
- Re-runs keep existing grades and visits, add only genuinely missing rows.

### Must never happen
- A grade, assessment, attendance, loan, or visit without a class + enrollment of the same year.
- A locked year accepting new academic/support rows.
- A re-run re-randomizing finished grades.
- 2027-2028 carrying academic/support rows before opening.
- Health details visible outside permitted roles.
- A whole class left blank/pending, or a "no grades" showcase gap.

### Edge cases and what happens then
- Existing grades on re-run → kept exactly, no new random scores.
- Enrollment missing class link → linked to same-subject twin first, then graded.
- Open loan on re-run → due date kept, same copy never double-lent.
- Missing trend month → only that month backfilled, history untouched.
- Current Term-3 assessment not yet built → no grade row at all (pending by absence, not by blank grade).

## 7. Out of Scope
- Changing grade scales, weights, promotion, or report formulas/layouts.
- New library/clinic workflows; conduct trends, attendance awards, most-borrowed lists (noted follow-ups).
- Backbone, students, payments (Children 1–3).

## 8. Success Checks
- [ ] Every class × 2024/2025 opens 12 assessments + full grades + ~95% attendance with weekday-scattered dates.
- [ ] Current year per class shows 8 complete + 2 Term-3 built with ~30% of those pending, never a whole blank class.
- [ ] Sample report cards printable per year with honors where earned; exactly 2 history fails visible, no gaps.
- [ ] Library hub per year shows ~30% borrow rate + ~15% late fines with quantities matching open loans; current shows ~10% open.
- [ ] Clinic hub per year shows seasonal trend (~15 peak / ~6 off months), not flat, with 1–2 referrals per term.
- [ ] 2027-2028 shows zero rows, nothing broken.
- [ ] Re-run re-randomizes and duplicates nothing.

## 9. Open Questions (if any)
None — volumes settled 2026-10-12 (options A throughout).

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: gradebooks, report cards, attendance sheets, library hub, clinic hub, year pickers (no layout change).
- Likely areas of the codebase (from code inspection 2026-10-12):
  - `Actual_Website/.../database/seeders/GradesAssessmentsSeeder.php:12-80` — today active-year only with `take(2)` fail + 1 no-grades gap and `now()` dates. Must: loop per `school_year` (2024-2025, 2025-2026, current); keep type×term matrix + weights (history 12/class, current 8+2); remove no-grades gap; limit fails to 2 students history-only; keep existing-grade skip; scatter `created_at` weekdays in Jun–Mar windows; current Term 3 ~30% grades pending (omit grade row or mark pending, never whole class).
  - Attendance (enrollment_subject-linked) — same year loop + scattered weekday dates; enforce ~95/5 split; current Term 3 ~70% built.
  - `.../seeders/LibraryAndClinicSeeder.php:16-58+` — books global via `updateOrCreate(isbn)` (keep). Today every-student × now()-60-days. Must: fan transactions/logs per year with borrow/due/return in year window; apply 30% borrow / 15% late / current 10% open rule; fine = lateDays × 5.00; keep student+book skip for idempotency; clinic reasons seasonal per §6 counts with student+complaint skip.
  - `app/helpers.php:84-92` — `school_year_locked()` refuses locked-year writes.
- Data/records touched: assessments, grades, attendance, library transactions, clinic logs — each stamped to its school year.
- Parent: `seed-data-connected-history.md` (Approved). Previous: `seed-data-payments-fulfilled.md` (Approved). This closes the child list.
- Roles/permissions involved: none changed.

## 11. Approval
> Approved by user on 2026-10-12.
