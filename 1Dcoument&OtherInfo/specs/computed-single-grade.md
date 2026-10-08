# Spec: Computed Single Grade

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Revision**: 2026-10-08 — final per-group weights (§9) and Quarterly Assessment naming locked in. Complete-words pass 2026-10-08. Gap fix 2026-10-08: legacy singular `Written Work` rows count as Written Works. Re-approved by user on 2026-10-08.
- **Implemented**: 2026-10-08 — shared GradeWeightService with per-group table + resolver, three-component formula with legacy folding, single adjustable Final column with applied-group label; verified visually by user (all 6 checks pass).
- **Parent**: `teacher-gradebook-overhaul.md` (child 2 of 4 — depends on parent + child 1's three types)

## 1. Why We Need This
The computed table shows two columns saying nearly the same thing — an auto number and an editable copy — while still splitting performance into four old buckets. One table on three components with a single adjustable Final column means the teacher reads once, tweaks borderlines, saves once.

## 2. Who Is Affected
* **Teachers** — only builders; read Written Works / Performance Tasks / Quarterly Assessment, adjust one Final column, batch-save.
* **Registrar / Principal** — read the same stored finals through report cards and unlock reviews; nothing new to learn.
* Untouched: students, parents, and the submit-to-lock rule.

## 3. Business Flow: Today vs After
- **As-is**: Table shows Written Work 20 / Quiz 20 / Seatwork 20 / Exam 40 + Computed + editable Final Grade → Batch Save Final Grades.
- **To-be**: Table shows Written Works / Performance Tasks / Quarterly Assessment using the class's weights row (§9) + single Final Grade pre-filled from auto-compute, adjustable → Batch Save Final Grades. Old Quiz/Seatwork rows count inside Written Works; old Exam rows count inside Quarterly Assessment.
- **Preserved**: Raw-score normalization (10/10 = 100%), Pending-on-save, Submitted-lock with skip-and-report, unlock-request approvals, report cards, promotion reads — all unchanged.
- **Exceptions**: A component with no scores contributes nothing (shown as —, not zero-dragged).

## 4. How It Should Work
1. Teacher opens Computed Grades for a class + period: Written Works / Performance Tasks / Quarterly Assessment columns with raw/max + %, then one Final Grade input pre-filled from auto-compute.
2. She adjusts borderlines directly in the Final inputs.
3. Batch Save Final Grades stores all rows as Pending; submitted rows are skipped with a plain count; success confirms counts saved + skipped.

## 5. Look & Feel (UX)
- Same table shell; columns become Student | Written Works | Performance Tasks | Quarterly Assessment (each header shows its class-specific percent, e.g. `Performance Tasks 50%`) | Final Grade.
- Final inputs keep today's styling, pre-filled, adjustable, 0–100.
- Key states: default (prefilled), loading, empty (no students/grades yet — plain next step), success/skip message, submitted-locked display, permission-denied.
- Plain words: `Written Works`, `Performance Tasks`, `Quarterly Assessment`, `Final Grade`.
- Fewest clicks: adjust-and-save in place; tab order runs down the Final column.

## 6. Business Rules
### Must always be true
- Components are exactly Written Works, Performance Tasks, Quarterly Assessment, weighted per the class's §9 row.
- The class's weights row is resolved automatically from its subject (and track/strand for SHS/TVL classes) — the teacher never picks weights.
- Old Quiz + Seatwork rows count inside Written Works, old singular `Written Work` rows count as Written Works, and old Exam rows count inside Quarterly Assessment, always and everywhere the formula runs.
- Each component normalizes raw ÷ max (10/10 = 100%) before weighting; empty component contributes nothing.
- Final inputs always pre-fill from auto-compute and stay adjustable until save.
- Save stores Pending, skips Submitted with a plain skipped count, audits with counts.
- Weights live in the one shared spot from child 1's list family — never a second copy.

### Must never happen
- Two grade columns must never return; a locked computed display must never ship.
- Quiz/Seatwork/Exam must never appear as columns or choices again.
- Old Quiz/Seatwork scores must never be dropped or zeroed by the re-map.
- Saved finals must never change shape for report cards, promotion, or principal views.
- Weights must never total anything but 100.

### Edge cases and what happens then
- All components empty → Final pre-fills blank/zero path as today (no fake 100%).
- Submitted rows in the batch → skipped + counted, rest save, message names the count.
- Locked school year → same frozen message as today, nothing saved.
- Saved page from before → refresh shows new columns; stored finals untouched.

## 7. Out of Scope
- Batch entry UI (child 1); search, attendance, rename (children 3–4).
- Per-subject weight splits (possible follow-up only if you ask).
- Report card or promotion changes.

## 8. Success Checks
- [ ] Table shows Written Works / Performance Tasks / Quarterly Assessment + single Final Grade; no Computed column, no Quiz/Seatwork/Exam anywhere.
- [ ] The class's §9 weights row applies (spot-check one Grades 1–10 class and one SHS/TVL class).
- [ ] Old Quiz/Seatwork/singular-Written-Work scores count inside Written Works, old Exam inside Quarterly Assessment (spot-check a class with history).
- [ ] 10/10 normalizes to 100%; empty component shows — and drags nothing.
- [ ] Final pre-fills from compute, adjusts, batch-saves as Pending; Submitted rows skip with count.
- [ ] Report cards + principal views read on undisturbed.

## 9. Open Questions (if any)
None — final per-group weights below, provided by you on 2026-10-08 (replaces the provisional 25/50/25; parent §9 holds the same table).

### Final weights (all rows total 100)
**Grades 1–10:** Languages/AP/ESP 30/50/20 · Science/Math 40/40/20 · MAPEH/EPP/TLE 40/40/20.
**SHS:** Core 25/50/25 · All other 25/45/30 · Work Immersion/Research/Business Enterprise Simulation/Exhibit Performance 35/40/25.
**TVL (TVL/Sports/Arts and Design):** All other 20/60/20 · Work Immersion/Research/Exhibit Performance 20/60/20.
(in Written Works / Performance Tasks / Quarterly Assessment order throughout.)

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: teacher Computed Grades table only.
- Likely areas (from code inspection): `TeacherController::computedGrades` (type list ~line 689 + weights ~lines 690-695 become the shared 3-type list + final per-group weights from §9; keep the `?? 0.25`-style guard equivalent); `computed-grades-results` columns plus single Final input (drop Computed column, keep Submitted-locked display branch); `batchSubmitGrades` unchanged (Pending/skip/audit already correct); old rows with type Quiz/Seatwork treated as Written Works and old Exam rows as Quarterly Assessment in the grouping step only (no data rewrite).
- Data/records touched: final grades stored as today. No migration, no backfill.
- Roles/permissions involved: owning teacher only (same checks).

## 11. Approval
> Approved by user on 2026-10-08.
