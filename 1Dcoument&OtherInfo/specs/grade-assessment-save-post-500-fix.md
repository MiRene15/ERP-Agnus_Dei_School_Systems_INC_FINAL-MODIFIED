# Spec: Grade Assessment Save Post 500 Fix

- **Status**: Approved (re-approved by user on 2026-10-12 — duplicate-type rule: separate rows, unique widened to add title + assessment_date; Amendment D demo year-relaxation approved by user on 2026-10-12)
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-12 (base + Amendments A/B + Rev C + duplicate-type amendment 2026-10-12 + Amendment D demo year-relaxation 2026-10-12)
- **Amendment**: 2026-10-11 — computed-table restyle + posted/on-hold notices + batch edit/view toggle + alphabetical class lists (approved by user on 2026-10-11). Amendment B 2026-10-12 — year badge + year picker on both save pages, cross-year block, both-saves crash-safe, subject-missing safe display (approved by user on 2026-10-12). Revision C 2026-10-12 — 3rd Term 500 on both batch sheet and solo per-student page even when the period is open (approved by user on 2026-10-12).

## 1. Why We Need This
Teachers fill in scores on the batch class sheet and the solo per-student page and tap Save or Post, but get a white 500 page instead — losing what they typed, unsure what saved. For 3rd Term this happens even when the period is open and the right term was edited. At grading-deadline peak this means late nights, copy errors, and mistrust. This fix makes both pages never show a 500, always carry and check the school year, and offer a year picker right on the page.

## 2. Who Is Affected
* **Teachers** — the only ones who save and post. They get sheets that never blow up, never eat input, always show which year they're in, and can switch years from the page itself.
* **Registrar / Principal** — read the posted finals through report cards and unlock reviews. They gain complete classes only, never half-posted, never mixed-year.
* **IT Admin** — sees the error log get quiet on these buttons.

## 3. Business Flow: Today vs After
- **As-is**: Open batch (`/teacher/classes/{class}/assessments?grading_period=…`) or solo (`/teacher/grade-assessment/{class}/student/{enrollment}`) → fill rows → tap Save/Post → possible white 500 → go back unsure what saved → re-type → risk of double grades. In 3rd Term the 500 happens on both pages even when the period is open and the right term was edited. Year lives on the list page; the two save pages inherit it from the class record, so a bookmarked old-year ID opens without warning.
- **To-be**:
  1. Teacher opens either save page → sees a **year badge + year picker** (defaults to the class's year) at the top.
  2. She fills rows, taps Save Scores or Post Grades.
  3. The sheet checks every row first **plus class-year = student-year = expected year**.
  4. Bad row or year mismatch? Stay on the sheet, flag plainly (row number or "wrong year — go back"), keep all input.
  5. Good? Save the whole scope at once, show "Saved N rows" or "Posted N rows." Post still locks as today.
  6. Any surprise failure still stays on the sheet with "Could not save — refresh and try again" — never a white 500.
  7. Changing the year picker navigates year context (back to the year-filtered list when the picked year is not this class's year) — never silently re-points the save.
- **Preserved**: Grading periods (1st/2nd/3rd Term carried in URL + hidden field), school-year lock, posted lock + unlock-request flow, only-own-class rule, whole-scope all-or-nothing save.
- **Exceptions**: Locked or posted students mixed in are skipped with names noted, rest saves. Empty sheet writes nothing. Wrong class, wrong year, or locked year shows a plain not-allowed note. Class with missing subject still opens with a plain weights fallback.

## 4. How It Should Work
1. Teacher picks class + grading period and fills the sheet (batch sheet or solo page). Year badge + picker show the class's year; grading period stays as today (including 3rd Term).
2. She taps Save Scores or Post Grades (batch) or Save (solo).
3. The button turns busy ("Saving…") and locks against a second tap until done — one tap means one save.
4. The sheet checks: every score is a number, score never above its max, date not in the future, class and students still exist, **class year = student year = expected year**, year not locked.
5. If anything is off, the sheet stays put, names the problem ("Row 12: score above max" / "This class belongs to S.Y. 2024–2025 — you picked S.Y. 2025–2026. Go back to the list"), keeps every typed row, and releases the button.
6. If all good, the scope saves at once and confirms counts. Post adds "(Posted — locked; request a correction unlock to change)."
7. Any surprise failure stays on the sheet with "Could not save — refresh and try again" and keeps input — never a white 500.
8. Changing the year picker to a year different from this class's year leaves the save page and returns to the year-filtered class list (no save attempted, no data moved).
9. Refresh shows the saved scores. Posted/locked rows read-only with unlock hint.

## 5. Look & Feel (UX)
- Where it lives: same batch sheet and solo per-student page. No new page, no new menu. Year badge + picker sit above the sheet next to the grading-period label.
- One primary action per spot: Save Scores / Post Grades (batch), Save (solo). Year picker is secondary, never the primary.
- One-tap safe buttons: tap → busy + disabled ("Saving…") → done → released. Double-tap never double-saves.
- Key states: default (sheet as today + year badge/picker), saving-busy, row error (row number + plain reason), year-mismatch (plain "wrong year" + back-to-list link, nothing written), success confirmation with counts, empty sheet ("nothing to save"), locked read-only with unlock hint, permission-denied ("not your class"), surprise-failure ("could not save — refresh and try again", input kept), subject-missing (sheet still opens, weights show plain "weights unavailable" fallback).
- Plain words only: `Save Scores`, `Post Grades`, `S.Y. 2025–2026`, `Row 12`, `Saving…`, `nothing to save`, `wrong year — go back`, `could not save — refresh and try again`. Dates in school format.
- Fewest clicks: no re-typing after any failure; picker defaults to the class's year; tab order unchanged.

### Amendment A (approved 2026-10-11, kept)
- Computed Grades mirrors the Encode grid table style; posted/on-hold banner + per-row pills; batch edit/view toggle; alphabetical class pickers.

### Amendment B (approved 2026-10-12)
- Year badge + year picker on **both** save pages; cross-year block; both saves crash-safe (plain note, never 500); subject-missing safe display.

### Revision C (approved 2026-10-12)
- 3rd Term explicitly in scope: both saves must work in 3rd Term when that term is open and edited; any surprise failure stays on-sheet with input kept, never 500.

### Amendment D — demo year-relaxation (approved 2026-10-12, demo only, revert after demo)
- Year-mismatch checks (class-year vs student-year vs expected year) on both save pages and both displays become **warnings, not blocks**: the save proceeds whole-scope as usual and the success note names the cross-year rows ("saved anyway for demo").
- Locked-year, posted-lock, ownership, and row-validity (number, max, future date) blocks stay in force — only the year rule is relaxed.
- Risk accepted by user for the demo window: cross-year saves can land in the wrong year's records and surface on the wrong report cards. Revert this amendment immediately after the demo.

## 6. Business Rules
### Must always be true
- One Save or Post writes its whole scope or nothing — checked first, saved together.
- A failed save keeps every entered row on screen, including added rows.
- A teacher never sees a 500 from these pages — every failure is a plain on-sheet note.
- The visible year (badge + picker) always matches the class's year on load.
- Class-year versus student-year (or versus expected year) blocks the save with names noted; nothing is written.
- Post still locks; locked rows need a correction unlock to change.
- Only the owning teacher can save; locked school years stay frozen.
- Double-tap counts as one save.
- A class with a missing subject still opens; weights fall back to a plain note, never a crash.
- 3rd Term saves only when that term is the chosen period; editing the right open term never blocks.

### Must never happen
- A save must never wipe input, never half-save, never show a white error page.
- A locked or posted student's rows must never be overwritten.
- Another teacher's class must never open or save.
- Old period or locked-year grades must never change through this sheet.
- Changing the year picker must never re-point the save into another year — it navigates, never rewrites.
- A current score must never land in the wrong year's records.

### Edge cases and what happens then
- 40+ rows or slow connection → saves together, no timeout page; second tap ignored while busy.
- Score above max or future date → hold the save, flag that row number, keep all input.
- All rows empty → "nothing to save" note, no write, no error page.
- Posted or locked student mixed in → skip just those, save the rest, name who was skipped.
- Stale bookmark to last year's class, or transferred student with enrollment year different from class year → "wrong year — go back" note, nothing written, never 500.
- Year picker switched to another year → leave to the year-filtered list, no save attempted.
- Class, student, or subject record missing → plain note on the sheet ("could not save — refresh and try again" / weights fallback), never a 500.
- Wrong class address or locked year → plain not-allowed note, never a 500.
- Second score of the same type for the same student in the same term (e.g. Quiz 1 + Quiz 2 both Written Works) → saves as separate rows, never blocks as "duplicate type". **Decided 2026-10-12 (pending re-approval): two same-type items with different title/date stay as separate rows; the unique key is widened to (enrollment_id, class_id, type, grading_period, title, assessment_date).**

## 7. Out of Scope
- Weights and computed formula changes.
- Report cards, promotion, principal views.
- Search, attendance shortcut, renames.
- Speed-tuning beyond stopping the 500.
- Any new audit screens.

## 8. Success Checks
- [ ] Fill a good 3rd Term batch sheet → Save shows saved count, no 500, refresh still shows scores.
- [ ] Post a good 3rd Term batch sheet → shows Posted + locks, no 500.
- [ ] Fill a good 3rd Term solo page → Save shows saved rows, no 500, refresh still shows them.
- [ ] One bad row (score above max) on either page in 3rd Term → stays on sheet, flags that row, keeps all input, no 500.
- [ ] Double-tap Save on slow net → one save only, button shows busy then releases.
- [ ] All rows empty → "nothing to save," no 500.
- [ ] Locked/posted student mixed in → rest saves, skipped names noted.
- [ ] Stale link to last year's class, or mismatched student year → plain "wrong year" note, nothing written, no 500.
- [ ] Locked/prior-year class → read-only with unlock hint; save attempt stays frozen with plain note.
- [ ] Open another teacher's class address → plain denied, no 500.
- [ ] Class with missing subject → sheet still opens (weights fallback), no 500.
- [ ] Year badge + picker visible on both save pages, defaulting to the class's year; switching years navigates to the year-filtered list without saving.
- [ ] Two Written Works items for one student in 3rd Term (Quiz 1 + Quiz 2) save as two rows and both re-appear on refresh.
- [ ] (Amendment A) Computed Grades renders in the Encode-grid table style; posted/on-hold banner + pills; batch View/Edit toggle; alphabetical pickers.

## 9. Open Questions (if any)
- What exact 500 message sits in the error log for the 3rd Term save? Nothing here may be guessed during implementation — paste the latest log lines so the fix targets the true crash. Copy-paste block: `Get-Content Actual_Website\ERP_Agnus_Dei_School_Systems_INC\storage\logs\laravel.log -Tail 120`
- Should two items of the same type in one term stay as separate rows (Quiz 1 + Quiz 2 both kept)? Recommendation: yes — that matches how teachers give multiple quizzes. Needs a yes before the data rule is changed.
- Is there a separate "grading period open/closed by date" switch for 3rd Term beyond the school-year lock? You said the period was open as per date — where do you open it, so the sheet can check the same switch?

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: batch sheet (`portal/teacher/assessments`), solo page (`portal/teacher/grade-assessment-student`), class picker shell (`portal/teacher/grade-assessment`), Computed Grades batch save (`computed-grades`).
- Likely areas (from read-only inspection 2026-10-12): batch display `assessments()` + `storeAssessments()` and solo display `gradeAssessmentStudent()` + `storeGradeAssessmentStudent()` in `app/Http/Controllers/Portal/TeacherController.php:348-515,695-863` with `teacher.grade-assessment*` / `teacher.assessments*` / `teacher.computed-grades.batch-submit` routes in `routes/web.php:256,263-264`; solo FormRequest `app/Http/Requests/Portal/StoreGradeAssessmentStudentRequest.php`; year helpers `active_school_year()`, `all_school_years()`, `school_year_locked()`; weight lookup `GradeWeightService::forClass($class->subject, …)` (null-safe, not the crash); views `resources/views/portal/teacher/assessments.blade.php` (grid sends `rows[][enrollment_id,type,title,assessment_date,raw_score,max_score,remarks]` via `flattened`) and `grade-assessment-student.blade.php` (sends `assessments[][type,title,raw_score,max_score,assessment_date,remarks]` with per-type `+ Add` buttons, base index `typeIndex*100`); solo write already has try/catch + `back()->withInput()` crash-catcher; log lines that assume related records exist stay wrapped; year badge/picker reads `Classes.school_year` with `Enrollment.school_year` cross-check on both reads and writes; picker change navigates via year-filtered list params (`school_year` + `grading_period` + `class_id`), never mutates the save target.
- Prime suspects for the 500 (not yet proven — need log): (a) data-rule clash — batch grid and solo `+ Add` both send multiple rows with the same (enrollment, class, type, period) but different titles, while migration `2026_08_14_115224_add_grading_period_to_assessments_table.php` enforces `UNIQUE (enrollment_id, class_id, type, grading_period)` with no title/date in the key — second same-type insert must fail; solo controller `TeacherController.php:769-784` even pre-rejects it as "duplicate assessment type — only one per type per period." Both contradict the batch spec "same student repeatable." Fix direction needs your call in §9: widen the unique key (add title + date) or aggregate per type — do not code until decided. Caught today as plain notes, but any uncaught path (e.g. computed post, log line, missing relation) still 500s. (b) uncaught 3rd Term-only path — both saves already wrap `DB::transaction` + `log_activity` in try/catch, so look for the throw outside the wrap (validation of `rows` vs `assessments` payload names, `assessment_date` column nullability, `Grade` post path).
- Data/records touched: assessment rows (scores per student per period) and stored final grades (Draft/Save/Posted states); year fields read-only in this fix. Possible data-rule change (unique key) flagged in §9 — no migration until approved. No rewrites of other tables.
- Roles/permissions involved: owning teacher only (same ownership + school-year-lock + posted-lock checks as today); registrar/principal read-only downstream.

## 11. Approval
> Approved by user on 2026-10-11 (base + Amendment A).
> Amendment B (year badge + year picker on both save pages, cross-year block, both-saves crash-safe, subject-missing safe display) approved by user on 2026-10-12.
> Revision C (3rd Term 500 on both saves even when period open) approved by user on 2026-10-12.
