# Spec: Fee Itemized Manager (Revision — Bundled Monthly Payments)

- **Status**: Implemented
- **Created**: 2026-10-12
- **Revised**: 2026-10-12
- **Approved by**: user on 2026-10-12

## 1. Why We Need This
Families on installment see a monthly tuition bill plus separate extra pays for Books, Uniform, ID, Miscellaneous, and the enrollment fee. Months feel unpredictable and the Cashier has to explain hand math.

This change bundles everything into one predictable monthly figure: pay the ₱1,500 downpayment once to get enrolled, then pay 10 equal months that already contain tuition plus Books, Uniform, ID, and Miscellaneous. Nothing extra is collected separately.

## 2. Who Is Affected
- **Cashier (primary)** — collects one monthly total. Sees the breakdown only when asked.
- **Families and students** — pay one equal amount each month. See Fully Paid / Has Balance plus what each month covers.
- **Directress** — still sets the 4-way split (Books + Uniform + ID + Miscellaneous) once per grade and year. No new screen.
- **Registrar** — still approves new installment files on the downpayment receipt alone. Monthly remainder never blocks a new file.
- **Librarian / Nurse** — their holds still block approval as today. This change does not loosen them.

## 3. Business Flow: Today vs After
- **As-is**: Installment = ₱1,500 first + full tuition spread over 10 months + the 4 add-on lines collected separately after the months. A family pays months and then still owes Books/Uniform/ID/Misc on top.
- **To-be**:
  1. Directress sets Books + Uniform + ID + Miscellaneous in 4 boxes that must add to the misc total.
  2. At enrollment the family picks Installment → pays Minimum ₱1,500 downpayment (this *is* the enrollment fee, counted once).
  3. The rest — tuition + all 4 add-ons together — is divided into 10 equal months. Each month already contains its share of Books, Uniform, ID, and Miscellaneous.
  4. New files advance on the downpayment receipt. Registrar grants full approval even though monthly remainder exists.
  5. Returnees with last-year dues still need Cashier clearance plus downpayment.
- **Preserved**: 10% discount touches tuition only, never ₱1,500 or add-ons. Posted bills are never rewritten silently. Library dues share one receipt. School-year locks. Discount approvals.
- **Exceptions**: mid-year enrollee, path switch full↔installment, scholarship tuition-waived, voided downpayment — see §6.

## 4. How It Should Work
1. Directress opens fees, picks school year, edits a grade → sees Tuition plus 4 boxes: Books, Uniform, ID, Miscellaneous → saves only when the 4 add to the misc total.
2. Cashier picks Installment → hint shows Minimum ₱1,500 downpayment + monthly amount (bundled).
3. Cashier takes the ₱1,500 first; below ₱1,500 is blocked naming the figure.
4. After downpayment, Cashier sees Month 1 total (bundled), paid-to-date, remaining, and status. Takes payment at or above that month's total; below is blocked naming the figure.
5. Registrar opens a new Pending file with Downpayment-confirmed + receipt badge → assigns section → taps Grant Full Approval → enrollment created even though monthly remainder exists.
6. Family view shows status + one monthly figure + "View breakdown" listing the Books/Uniform/ID/Misc share inside that month.

## 5. Look & Feel (UX)
- Where it lives: existing Directress fee form (4 boxes, primary `Save Fees`), existing Cashier payment form (bundled hint, primary `Take Payment`), existing Registrar approval list (truthful block messages, primary `Grant Full Approval`). No new menu.
- What the user sees first: Cashier sees month total + status first, breakdown second. Family sees Fully Paid / Has Balance + next month due first, breakdown second.
- Key states: default, empty split (what to fill next), sum-mismatch error naming the difference, below-floor error naming the figure, Fully Paid green, Has Balance amber with next due, hold-blocked red naming library/clinic/finance-returnee only, permission-denied as today.
- Plain-language labels: Books, Uniform, ID, Miscellaneous, Minimum, Downpayment, Month Total, View breakdown, Print. Current school year pre-filled.
- Peak-load note: enrollment and fee-due days — totals once per screen load, queues paginated with counts.

## 6. Business Rules
### Must always be true
- Books + Uniform + ID + Miscellaneous always add to the misc total on save.
- Installment family total = ₱1,500 (once, first, fixed, never discounted) + 100% tuition + 100% of the 4 lines bundled into months.
- Each bundled month = (tuition + Books + Uniform + ID + Miscellaneous) ÷ months left, in equal shares. Nothing is collected separately after.
- Full = 90% tuition + 100% of the 4 lines, no ₱1,500 anywhere in it (unchanged).
- New applicants enroll on downpayment receipt; monthly remainder does not block them.
- Returnees with prior-year dues need clearance plus downpayment.
- Library overdue and open clinic cases still block approval for everyone.

### Must never happen
- A separate Books/Uniform/ID/Misc/enrollment collection on top of the monthly total.
- A full total containing ₱1,500, or a discount on add-ons or ₱1,500.
- A below-month payment accepted.
- A new installment file blocked only because monthly remainder exists.
- Posted bills rewritten silently; old figures stand until path-switch.
- Prices set inside the payment screen.

### Edge cases and what happens then
- Mid-year enrollee → remaining (tuition + add-ons) ÷ remaining months; ₱1,500 still in full once.
- Path switch after paying → single re-computation, prior payments credited enrollment-first then bundled months in order, history kept.
- Scholarship tuition-waived → pays add-ons share spread monthly, no tuition share.
- Downpayment voided before approval → back to Awaiting Downpayment, history kept.
- Fee edited mid-year → new assessments only; posted lines keep figures.
- Years with no split → treated as one Miscellaneous line until Directress re-saves the 4-way split.

## 7. Out of Scope
- Recomputing old bills under the new bundled months automatically.
- Online payments or auto-debit.
- Printed receipt restyling.
- Per-level custom month counts (uniform 10).

## 8. Success Checks
- [ ] Picking Installment shows Minimum ₱1,500, then 10 equal bundled months with no separate add-on pay.
- [ ] Paying ₱1,500 unlocks Registrar Grant Full Approval for a new file.
- [ ] Below-month payment is blocked naming the month total.
- [ ] Cashier month view shows one total + View breakdown with Books/Uniform/ID/Misc shares.
- [ ] Family view shows Fully Paid / Has Balance + named breakdown.
- [ ] Returnee with last-year dues still blocked until clearance; library/clinic holds still block.

## 9. Open Questions (if any)
None — downpayment + bundled months, tuition-only 10%, edge handling, UX placement, out-of-scope, and success checks confirmed in chat 2026-10-12.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: Directress fee create/edit + fees list, Cashier payment form + financial view, Registrar approval list, family ledger view.
- Likely areas of the codebase (from read-only inspection 2026-10-12):
  - Fee setup: `FeeSchedule` with `misc_fee_items` split via `FeeAssessmentService::splitSchedules` — keep 4-way sum validation, no change.
  - Assessment change: `FeeAssessmentService::assessLedger` installment branch now posts `enrollment line + N bundled month lines` each with `term_label` showing the Books/Uniform/ID/Misc share (via `bundledBreakdown`). `equalSplits` divides tuition-only; misc shares recovered from `splitBases` fallback. `total_assessed` formula unchanged (₱1,500 + tuition + misc). `fullTotalFor` and full branch unchanged.
  - Previews/floors: `CashierController@showPayment/@processPayment` + `DownpaymentService::confirm` + `floorFor` (earliest unpaid line) — floor becomes bundled month total automatically once lines are bundled; keep below-floor guard. When no ledger yet + installment picked, floor defaults to ₱1,500 enrollment fee (unchanged).
  - Gate: `HoldService::forStudent / forApproval` + `FullApprovalService::approve` — keep narrow rule: current-year installment remainder with receipt does not block new files; prior-year dues, library overdue, open clinic still block.
  - Switch: `switchPath` credit order enrollment-first then bundled months; recover bases via `splitBases` (updated to handle bundled ledgers with no separate misc lines).
- Data/records touched: fee schedules by school year (4-way split unchanged), new assessments/path-switches only (bundled months), downpayment receipts, holds raise/lift history; posted lines immutable.
- Roles/permissions involved: directress (setup), cashier (collect), registrar (approve), families (read); no permission-model change.

## 11. Approval
> Approved by user on 2026-10-12.