# Spec: Cashier Installment Form Load Fix

- **Status**: Approved
- **Created**: 2026-10-12
- **Approved by**: user on 2026-10-12

## 1. Why We Need This

When the cashier clicks Take Payment for a family paying in installments who has paid before, the small payment window shows "Could not load the payment form" instead of the form. The queue stalls, families wait, and the cashier cannot take the next monthly payment. First-time installment accounts and full-payment accounts still open fine. This fix makes the form open every time with the true month total.

## 2. Who Is Affected

- **Cashier (primary)** — repeat installment visits open again; no new steps to learn.
- **Families and students** — shorter wait and a truthful month figure.
- **Registrar** — reads the same receipt later; approval work does not change.
- **Directress, Librarian, Nurse, Teachers, IT Admin** — untouched; no new permissions.

## 3. Business Flow: Today vs After

- **As-is**: cashier clicks Take Payment on a repeat installment account → red error box "Could not load the payment form" with Retry → Retry fails the same way → monthly payment cannot be taken at the counter.
- **To-be**:
  1. Cashier clicks Take Payment → the form opens.
  2. The hint shows the true month total with its real breakdown.
  3. Cashier takes the payment, receipt prints, and the success pop-up reads as today.
  4. The only thing removed is the crash plus the made-up placeholder shares.
- **Preserved**: how money posts, discount approvals, receipt numbering, audit trail, protection against double-press, school-year locks, library dues sharing one receipt, Fully Paid gating.
- **Exceptions**: corrections and voids, locked years, overpayments credited forward, and library-dues receipts all keep working as today; this fix only changes what the month box shows, never whether a payment is allowed.

## 4. How It Should Work

1. Cashier searches for the student and clicks Take Payment on a repeat installment account → the form opens within the usual short wait.
2. The form shows the Minimum/month total matching the family's financial view, with the real breakdown of what that month covers.
3. Cashier enters an amount at or above the month total and presses Take Payment → payment posts exactly as today.
4. Below-month amounts are still refused with a message naming the month total.
5. Fully Paid accounts still show Fully Paid with no payment form; first-time and full-payment forms look exactly as today.

## 5. Look & Feel (UX)

- Where it lives: the same Take Payment pop-up from the Process Payments list. One primary action: Take Payment. Secondary: Close.
- What the cashier sees first: month total and status, then the breakdown, then the amount box.
- Key states: loading shimmer while opening; open form with true total; below-floor error naming the figure; success pop-up unchanged (true balance + percent + receipt buttons); the red "Could not load" box appears only on a real network failure now.
- Plain-language labels: Minimum, Month Total, What this month covers, Take Payment, Remaining balance, % paid. All pesos shown the same way (₱1,234.56). No made-up figures.
- Fewer clicks: nothing new to fill in; the correct numbers arrive with the form.
- Peak-load note: fee-due days — opening the form must not get slower or add extra waiting.

## 6. Business Rules

### Must always be true

- The form always opens for installment accounts with prior receipts.
- The month total shown always equals the earliest unpaid bundled month on that account.
- The breakdown shown always comes from that account's own stored month line, never from fixed sample numbers.
- Pesos are formatted once from real amounts (divide first, then format).

### Must never happen

- A repeat installment account showing "Could not load the payment form" when the network is fine.
- A hardcoded sample share (₱2,080 / ₱520 / ₱1,560 or similar) shown as if it were that family's bill.
- A display fix changing how much money posted, which discount applied, or whether an approval is granted.
- Full-plan, first-time installment, and downpayment forms changing in look or behavior.

### Edge cases and what happens then

- Overpayment with change credited forward → posts and reads exactly as today.
- Library dues ticked on the receipt → share one receipt as today; percent still reflects the tuition account only.
- Fully Paid account → Fully Paid message, no payment form, as today.
- Voided then re-opened payment → history-corrected account reads as today.
- Locked school year → no new payment can post; everything stays read-only as today.
- Mid-year shorter month counts → month total is whatever the account's earliest unpaid month says, as today.
- Plan-switch accounts (full ↔ installment) → posted history stands, remainder reads as today.

## 7. Out of Scope

- Changing fee amounts, discount math, receipt math, or approval rules.
- Recomputing old bills under any new month math.
- Per-level custom month counts.
- Online payments or auto-debit.
- Printed receipt restyling.
- A bigger month-breakdown rework (follow-up spec).

## 8. Success Checks

- [ ] A repeat installment account opens the payment form (no red error box).
- [ ] The month total in the form matches the financial view for that student.
- [ ] A below-month amount is refused with a message naming the month total.
- [ ] Full-plan and first-time installment forms look and behave as before.
- [ ] The success pop-up still shows the true remaining balance with its matching percent.
- [ ] No hardcoded sample shares appear anywhere in the month box.

## 9. Open Questions (if any)

None.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: Take Payment pop-up form only — `portal/cashier/partials/payment-form.blade.php` (installment-only "What this month covers" box, ~lines 86–106), opened via `portal/cashier/partials/payment-modal.blade.php` fetch to `GET /cashier/payment/{student}?ajax=1`, full-page fallback `portal/cashier/payment.blade.php`.
- Likely areas of the codebase (files, routes, tables) — from read-only inspection 2026-10-12:
  - Root cause: the month box divides after formatting (`number_format(tuition, 2)` string with comma divided by 10) instead of dividing the raw amount first and formatting once; plus fixed placeholder shares in the same box. It only renders when the account is plan-locked to installment, which is why only repeat installment opens fail (the framework's error handler turns the non-numeric warning into a 500 on the ajax render).
  - Server data already available in the same render: `CashierController@showPayment` passes `termFloor` (earliest-unpaid month total via `floorFor`), `feeStatus` (via `statusFor`), and ledger lines carrying the stored per-month breakdown text (`term_label`, e.g. "Incl. Tuition ₱… + Books ₱…"). Use those; add no new queries.
  - Keep `processPayment`/`DownpaymentService::confirm` money paths, `allocatePayment`, `switchPath`, receipt numbering, and `payment-success` reply untouched.
- Data/records touched: display-only; no new or changed stored records.
- Roles/permissions involved: cashier (opens form); families/registrar read the same totals later; no permission-model change.

## 11. Approval

> Approved by user on 2026-10-12.
