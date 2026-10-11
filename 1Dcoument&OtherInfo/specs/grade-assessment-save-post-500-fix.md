# Spec: Grade Assessment Save Post 500 Fix

- **Status**: Approved
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-11

## 1. Why We Need This
Teachers fill in scores on the Grade Assessment sheet and tap Save or Post, but get a white 500 error page instead. They don't know what saved, lose what they typed, and must re-type everything. At grading-deadline peak this means late nights, copy errors, and mistrust in the gradebook. This fix makes Save and Post never show a 500 — every problem stays on the sheet with a plain note and all input kept.

## 2. Who Is Affected
* **Teachers** — the only ones who save and post. They get a sheet that never blows up and never eats input.
* **Registrar / Principal** — read the posted finals through report cards and unlock reviews. They gain complete classes only, never half-posted.
* **IT Admin** — sees the error log get quiet on these two buttons.

## 3. Business Flow: Today vs After
- **As-is**: Open class → fill rows → tap Save Scores or Post → white 500 page → go back unsure what saved → re-type → risk of double grades.
- **To-be**:
  1. Teacher opens the class sheet, fills rows, taps Save Scores or Post.
  2. The sheet checks every row first.
  3. Bad row? Stay on the sheet, flag that row number plainly, keep all input.
  4. Good? Save the whole class at once, show "Saved 38 rows" or "Posted 38 rows."
  5. Post still locks as today — changes need a correction unlock.
- **Preserved**: Grading periods, school-year lock, posted lock + unlock-request flow, only-own-class rule, whole-class all-or-nothing save.
- **Exceptions**: Locked or posted students mixed in are skipped with names noted, rest saves. Empty sheet writes nothing. Wrong class or locked year shows a plain not-allowed note.

## 4. How It Should Work
1. Teacher picks class + grading period and fills the sheet (batch sheet or per-student page).
2. She taps Save Scores or Post Grades.
3. The button turns busy ("Saving…") and locks against a second tap until done — one tap means one save.
4. The sheet checks: every score is a number, score never above its max, date not in the future, class and students still exist.
5. If anything is off, the sheet stays put, names the row ("Row 12: score above max"), keeps every typed row, and releases the button.
6. If all good, the whole class saves at once and confirms counts. Post adds "(Posted — locked; request a correction unlock to change)."
7. Refresh shows the saved scores. Posted rows read-only with unlock hint.

## 5. Look & Feel (UX)
- Where it lives: same Grade Assessment class sheet and per-student page. No new page, no new menu.
- One primary action per spot: Save Scores / Post Grades.
- One-tap safe buttons: tap → busy + disabled ("Saving…") → done → released. Double-tap never double-saves.
- Key states: default (sheet as today), saving-busy, row error (row number + plain reason), success confirmation with counts, empty sheet ("nothing to save"), locked read-only with unlock hint, permission-denied ("not your class").
- Plain words only: `Save Scores`, `Post Grades`, `Row 12`, `Saving…`, `nothing to save`. Dates in school format.
- Fewest clicks: no re-typing after any failure; tab order unchanged.

## 6. Business Rules
### Must always be true
- One Save or Post writes the whole class or nothing — checked first, saved together.
- A failed save keeps every entered row on screen, including added rows.
- A teacher never sees a 500 from these two buttons — every failure is a plain on-sheet note.
- Post still locks; locked rows need a correction unlock to change.
- Only the owning teacher can save; locked school years stay frozen.
- Double-tap, double-click, or impatient re-tap counts as one save.

### Must never happen
- A save must never wipe input, never half-save a class, never show a white error page.
- A locked or posted student's rows must never be overwritten.
- Another teacher's class must never open or save.
- Old period or locked-year grades must never change through this sheet.

### Edge cases and what happens then
- 40+ rows or slow connection → saves together, no timeout page; second tap ignored while busy.
- Score above max or future date → hold the save, flag that row number, keep all input.
- All rows empty → "nothing to save" note, no write, no error page.
- Posted or locked student mixed in → skip just those, save the rest, name who was skipped.
- Class, student, or subject record missing → plain "could not save — please refresh and try again" on the sheet, never a 500.
- Wrong class address or locked year → plain not-allowed note, never a 500.

## 7. Out of Scope
- Weights and computed formula changes.
- Report cards, promotion, principal views.
- Search, attendance shortcut, renames.
- Speed-tuning beyond stopping the 500.
- Any new audit screens.

## 8. Success Checks
- [ ] Fill a good sheet → Save shows saved count, no 500, refresh still shows scores.
- [ ] Post a good sheet → shows Posted + locks, no 500.
- [ ] One bad row (score above max) → stays on sheet, flags that row, keeps all input, no 500.
- [ ] Double-tap Save on slow net → one save only, button shows busy then releases.
- [ ] All rows empty → "nothing to save," no 500.
- [ ] Locked/posted student mixed in → rest saves, skipped names noted.
- [ ] Open another teacher's class address → plain denied, no 500.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: teacher Grade Assessment class view + batch sheet, per-student page (`grade-assessment-student`), Computed Grades batch save (`computed-grades`).
- Likely areas of the codebase (from code inspection): teacher grade-assessment + computed batch-save actions with existing `teacher.grade-assessment*` / `teacher.computed-grades.batch-submit` routes; per-student save with its row-indexed assessment payload and delete-then-insert per student/period; batch post with whole-class single-transaction and skipped-locked handling; shared assessment-type list; one-tap busy/disabled button behavior per the safe-actions pattern; activity log lines that assume related records exist.
- Data/records touched: assessment rows (scores per student per period) and stored final grades (Draft/Save/Posted states). No new tables, no rewrites of other tables.
- Roles/permissions involved: owning teacher only (same ownership + school-year-lock + posted-lock checks as today); registrar/principal read-only downstream.

## 11. Approval
> Approved by user on 2026-10-11.
