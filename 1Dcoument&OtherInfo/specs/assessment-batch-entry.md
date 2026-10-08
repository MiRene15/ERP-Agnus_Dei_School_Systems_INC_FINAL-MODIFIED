# Spec: Assessment Batch Entry

- **Status**: Approved
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Revision**: 2026-10-08 — Examination → Quarterly Assessment; complete-words pass; dynamic Add-Row sheet remodel with entry buttons (Implemented 2026-10-08). Follow-up 2026-10-08: double-confirm row removal + prepend-new-rows-at-top (§6). Re-approved by user on 2026-10-08.
- **Implemented**: 2026-10-08 — additive date/remarks columns, shared 3-type list, dynamic batch sheet (picker + LRN auto + per-row types + top actions + readable scores) with row-indexed transactional store and input-preserving validation; verified visually by user.
- **Parent**: `teacher-gradebook-overhaul.md` (child 1 of 4 — no dependencies beyond parent)

## 1. Why We Need This
A teacher with 40 students opens 40 separate pages to encode one quiz. One batch sheet — one row per student, one Save — turns an evening of clicking into minutes, with no re-typing between screens where errors breed.

## 2. Who Is Affected
* **Teachers** — only builders; encode a whole class in one sheet with dates and notes.
* **IT Admin** — runs one small additive data update once.
* Untouched: students, parents, registrar, principal, and the per-student page (keeps working).

## 3. Business Flow: Today vs After
- **As-is**: Open class → open student 1 → type scores → save → back → open student 2 → … Scores carry no date and no remarks; types are Written Work, Quiz, Seatwork, Exam.
- **To-be**:
  1. Teacher opens the class batch sheet for a grading period: Date | LRN | Student Name | Type (Written Works / Performance Tasks / Quarterly Assessment) | Scores raw/max | Remarks.
  2. She fills rows, one Save posts the class.
  3. Per-student page keeps working with the same three types.
- **Preserved**: Grading periods, school-year lock, submitted-grade lock, unlock-request flow — all unchanged.
- **Exceptions**: A failed save keeps every row on screen with plain row-level notes — never half-saved, never wiped.

## 4. How It Should Work
1. Teacher picks class + period and taps Batch Entry (on the Grade Assessment class view or the master-list header) → one dynamic sheet.
2. Each row, left to right: Date (defaults today) | LRN (auto-filled, read-only) | Student Name (picker from active students) | Type dropdown (Written Works / Performance Tasks / Quarterly Assessment) | Scores (raw + max) | Remarks/Notes (right).
3. Existing rows for the period list first, editable; Add Row appends blank rows (same student repeatable for multiple items).
4. One Save validates all rows, posts the class, confirms counts saved.
5. Fully-empty rows (no student, no scores, no remarks) are skipped, never stored.

## 5. Look & Feel (UX)
- Lives on the class Assessments sheet as one dynamic table (no type tabs): Date | LRN | Student Name picker | Type | Scores | Remarks, one Add Row button, one Save Scores action. Reached by Batch Entry tap from the Grade Assessment class view (period carried over) or the master-list header; per-student pages stay reachable.
- One primary action: Save Scores. Same card shell as today.
- Key states: default (existing rows + one blank row), loading, empty class (plain next step + Add Row still available), row error (which row, plain reason), success confirmation, submitted-locked rows (read-only with unlock hint), permission-denied.
- Plain words only: `Written Works`, `Performance Tasks`, `Quarterly Assessment`; dates in school format.
- Fewest clicks: type once per row via dropdown defaulting to the last-used type; tab order follows the sheet left-to-right.

## 6. Business Rules
### Must always be true
- One Save posts the whole sheet or nothing — validated first, single transaction.
- Every row carries its own student picker, date (default today, editable), type, scores, and optional remarks; LRN fills itself from the picked student and is never typed.
- The same student is repeatable across rows (multiple items per student).
- Types are exactly Written Works, Performance Tasks, Quarterly Assessment — one shared list with the per-student page.
- Raw score never exceeds its max on save (flagged per row, save held).
- School-year lock and submitted-grade lock behave exactly as today (locked students' rows are skipped, never saved).
- Removing a row always asks twice before it leaves the sheet (removed rows delete on Save).

### Must never happen
- A save must never wipe entered input on failure — failed saves re-render every entered row (including added rows) from the submitted input.
- The sheet must never split into per-type tabs; one table, type chosen per row.
- Quiz/Seatwork/Exam must never appear as choices for new rows — old Quiz/Seatwork rows stay readable (counted as Written Works by child 2), old Exam rows stay readable (counted as Quarterly Assessment).
- A locked (submitted) student's rows must never save — same unlock flow as today.
- Another teacher's class must never open — same ownership check as today.

### Edge cases and what happens then
- 40+ rows with one bad row → held, bad rows flagged, all input kept.
- All rows empty → plain `nothing to save` note, no write.
- Date in the future → flagged per row, save held.
- Saved page from before → refresh shows the new menu; nothing to reconcile.

## 7. Out of Scope
- Weights and the computed formula (child 2); old Quiz/Seatwork re-mapping (child 2 decides, explicitly).
- Search, attendance shortcut, rename (children 3–4).
- Report cards, promotion, principal views.

## 8. Success Checks
- [ ] Batch sheet is one dynamic table: Date | LRN (auto) | Student Name picker | Type | Scores | Remarks, with Add Row.
- [ ] Picking a student fills their LRN; the same student is repeatable for multiple items.
- [ ] A Batch Entry button on the Grade Assessment class view opens the sheet with the period preserved.
- [ ] A Batch Entry button on the master-list header opens the sheet.
- [ ] One Save stores all rows with dates and remarks; counts confirmed on screen.
- [ ] Bad row holds the save, flags the row plainly, keeps all input.
- [ ] Type dropdown offers exactly the three types on sheet and per-student page.
- [ ] Old Quiz/Seatwork rows still display; locked-year and submitted locks behave as today.
- [ ] Another teacher's class address is denied.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: teacher Grade Assessment class view (+batch sheet), per-student page (type list only).
- Likely areas (from code inspection): Assessments sheet rebuilt as one dynamic table (Add Row + per-row student picker with LRN auto-fill, per-row type dropdown from the shared list — replaces the per-type tab tables); batch store re-keyed to row-indexed payload (`rows[][enrollment_id, type, assessment_date, raw_score, max_score, remarks]`, delete-then-insert per class/period excluding submitted-locked students, one transaction, per-row errors re-rendered from submitted input); ownership (`teacher_id`) + `school_year_locked()` + submitted-`Grade` checks kept.
- Data/records touched: assessment rows (additive columns; no rewrites of other tables, no backfill).
- Roles/permissions involved: owning teacher only (same checks as today).

## 11. Approval
> Approved by user on 2026-10-08.
