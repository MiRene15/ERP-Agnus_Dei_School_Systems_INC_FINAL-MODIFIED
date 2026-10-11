# Spec: Cashier Full-Payment Label

- **Status**: Implemented
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-11
- **Implemented**: 2026-10-12 — full = 90% tuition + 100% misc (no ₱1.5k), installment = ₱1.5k + 100% all, plan-bound hint, Print-only receipt rows. Verification of §8 checks pending user testing; open for re-open on bug reports. No commit yet.

## 1. Why We Need This

When a family chooses Full Payment, the cashier's screen never shows the full amount due — the only hint on screen is the installment-side minimum (₱1,500). The cashier has to compute the full total by hand at the busiest moment of enrollment, which invites mistakes and slows the queue. Separately, every receipt row shows two links (Print and View) when one is enough, adding clutter where speed matters.

This change shows the exact full amount the moment Full Payment is picked — with every line named — and trims receipt rows to Print only.

## 2. Who Is Affected

- **Cashier (primary)** — sees the exact full total instantly, blocks underpayments by naming it, tidier receipt rows.
- **Families and students** — told one clear full amount instead of a computed-on-the-spot figure.
- **Registrar** — full-path approvals read the same totals; no process change.
- **Directress / Principal** — set tuition and misc fees as today; the itemized split (books, uniform, ID, miscellaneous) now shows under the full total.
- **IT Admin** — no new permissions or tables.

## 3. Business Flow: Today vs After

- **As-is**: cashier picks Full Payment from the plan list; the amount hint stays at "Minimum ₱1,500 — Enrollment Fee"; the full total is computed mentally. Receipt rows show Print + View.
- **To-be**:
  1. Cashier picks a plan → the amount hint instantly swaps: Installment shows the minimum plus the ₱1,500 downpayment line; Full shows the full total with named lines (discounted tuition, books, uniform, ID, miscellaneous).
  2. Cashier collects at or above the shown total; anything below is blocked naming the exact figure.
  3. Receipt rows show Print only.
- **Preserved**: discount approvals, receipt + audit rails, idempotency/press protections, school-year locks, library-dues-on-shared-receipt, graduation-fee marking.
- **Exceptions**: mid-year full payer (90% off remaining tuition + full remaining misc, no ₱1,500); path switches re-compute once crediting prior payments in order; old ledgers assessed under the previous math keep their posted figures.

## 4. How It Should Work

1. Cashier opens the payment form and picks **Installment** → hint reads the minimum due with the ₱1,500 downpayment line named.
2. Cashier picks **Full Payment** → hint swaps to the full total: ₱1,500 excluded, tuition at 90%, books/uniform/ID/miscellaneous at 100%, each line named with its amount.
3. Cashier enters an amount below the shown total → blocked with a message naming the exact required figure.
4. Cashier opens the financial view → each payment row offers Print only; the attached-file View link is gone.
5. Ticked library dues still join the same receipt; graduation fees are still marked paid separately — both visible alongside, neither inside the plan totals.

## 5. Look & Feel (UX)

- Where it lives: the existing Cashier payment form and financial view — no new screen. One primary action per step: pick plan, Take Payment, Print.
- What the cashier sees first: the amount hint directly under the amount box, updating the instant the plan changes.
- Key states: installment hint (Minimum + downpayment), full hint (Full total + named lines), below-floor error (names the figure), Fully Paid (unchanged).
- Plain labels: Full total, Minimum, Downpayment, Books, Uniform, ID, Miscellaneous, Print. Amounts uniform.
- Peak-load note: fee-due days — totals computed server-side once per screen load; no extra waiting per keystroke.

## 6. Business Rules

### Must always be true

- Full payment = 90% of tuition + 100% of misc/books/uniform/ID, with no ₱1,500 line.
- Installment = ₱1,500 downpayment (first, fixed, never discounted) + 100% tuition + 100% misc.
- The 10% touches tuition lines only — never the ₱1,500, never misc/books/uniform/ID.
- The amount hint always matches the picked plan and names the exact figure the floor enforces.
- Every receipt row offers Print; no View link on any receipt row, page or modal.
- Library dues ticked at payment share the single receipt; graduation fees stay separately markable — both outside the plan totals.

### Must never happen

- A full total containing the ₱1,500 line.
- A discount applied to misc/books/uniform/ID or to the ₱1,500.
- A below-total payment accepted on either plan.
- A View link on any receipt row after this change.
- Posted assessments silently rewritten — old figures stand; new math applies to new assessments and path switches only.
- Prices set or changed inside the payment screen.

### Edge cases and what happens then

- Mid-year full payer → 90% off remaining tuition + 100% remaining misc, no ₱1,500.
- Path switch after paying → single re-computation, prior payments credited in order, history kept.
- Scholarship (tuition waived) full payer → misc only, at full price.
- Honor/sibling discounts → computed as today on top of the plan totals.
- Locked school year → everything read-only, exactly as today.

## 7. Out of Scope

- Recomputing old ledgers assessed under the previous bundle-discount math.
- A fee-manager editor for the itemized books/uniform/ID split.
- Online payments or auto-debiting.
- Printed receipt restyling.

## 8. Success Checks

- [ ] Picking Full shows the full total with named lines, books/uniform/ID at full price.
- [ ] Full total = 90% tuition + 100% misc, with no ₱1,500 anywhere in it.
- [ ] Picking Installment shows the minimum with the ₱1,500 downpayment line.
- [ ] Paying below a full total is blocked naming the exact figure.
- [ ] Receipt rows show Print only — no View link on page or modal.
- [ ] Ticked library dues still share the single receipt.
- [ ] Graduation fees still markable paid in the financial view.

## 9. Open Questions (if any)

None — tuition-only 10%, installment-only ₱1,500, add-ons-separate, and posted-figures-stand all confirmed 2026-10-11.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: Cashier payment form (`portal/cashier/partials/payment-form.blade.php` — plan select + Minimum hint), financial view receipt rows (`portal/cashier/partials/student-financial-results.blade.php:194-201` — Print + View links; same partial serves page and modal).
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - Totals: `FeeAssessmentService::assessLedger` currently discounts the tuition+misc bundle (`FeeAssessmentService.php:70-72`) — must split to 90% tuition + 100% misc with no enrollment line on the full path; same for `switchPath` (`:143-159`) and the previews in `CashierController.php:356-357` and `DownpaymentService.php:163-168`. Pre-assessment floor on the Cashier screen is always ₱1,500 (`CashierController.php:357`, `fullFloor` computed but never rendered) — render it and bind the plan select to swap the hint.
  - Amends `cashier-fee-rules.md` §6 rule 1 (1.5k on every enrollment → installment-only) and the "−10% on non-enrollment lines" wording (→ tuition lines only); amendment noted there on approval.
  - View-link removal: delete the `receipt_file_path` View anchor; keep the Print link and the Void flow untouched.
- Data/records touched: new assessments/path-switches only; posted ledger lines immutable; no migration (totals are computed, receipt rows are display).
- Roles/permissions involved: cashier (collect), directress/registrar (fee setup, unchanged), families (read totals); no permission-model change.

## 11. Approval

> Approved by user on 2026-10-11.
