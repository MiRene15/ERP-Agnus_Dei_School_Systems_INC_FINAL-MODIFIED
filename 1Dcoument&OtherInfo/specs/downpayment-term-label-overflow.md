# Spec: Downpayment Term Label Overflow

- **Status**: Implemented
- **Created**: 2026-10-12
- **Approved by**: user on 2026-10-12
- **Implemented**: 2026-10-12 — widened ledger_lines.term_label 40→255; overflow throw gone per log sweep (full 4-tap verification pending user confirm at push).

## 1. Why We Need This
Every installment downpayment fails and the Cashier sees a long database error. The monthly breakdown line (tuition plus books, uniform, ID, miscellaneous) is longer than the space saved for it, so the save is rejected and the file stays stuck waiting for payment. Widen the space so the full line saves and confirmations go through.

## 2. Who Is Affected
- **Cashier (primary)** — installment confirms work again; breakdown line still shows in full.
- **Registrar** — stuck files return as Pending with receipts; approvals resume.
- **Families and students** — "Pay at Cashier" clears after payment.
- **IT Admin** — runs one short update once.

## 3. Business Flow: Today vs After
- **As-is**: forwarded file → Cashier taps Confirm → save rejected (text too long) → truthful error shown by prior fix, file stays waiting.
- **To-be**: forwarded file → Cashier taps Confirm → monthly lines save with full breakdown text → receipt issued → file returns to Registrar as Pending with receipt badge.
- **Preserved**: no receipt means no advance; double-taps record money once; every attempt keeps history; locked years still block.
- **Exceptions**: failed saves still never advance the file; voided receipts still drop the file back to waiting with history kept.

## 4. How It Should Work
1. IT Admin runs the one-time update (provided as a copy-paste step).
2. Cashier re-taps Confirm on a stuck file with plan installment and amount at or above the minimum.
3. Monthly lines save with the full breakdown (`Incl. Tuition + Books + Uniform + ID + Miscellaneous`); receipt shows; file returns to Registrar as Pending.
4. Bulk retry works the same per file; blocked files name their reason.

## 5. Look & Feel (UX)
- No screen change. Same Cashier list and payment screen, same one `Confirm Downpayment` button.
- Cashier sees first: receipt on success; full breakdown line on the month; reason + safe-to-retry only on genuine failures.
- States: success (receipt + returned note), already-confirmed (existing receipt, no new charge), error-with-reason (unchanged from prior fix).

## 6. Business Rules
### Must always be true
- The full breakdown text is always kept — never chopped to fit.
- A file advances only when a receipt record exists.
- Double-taps record money exactly once.
- Every confirm and retry keeps history.

### Must never happen
- A file advancing when monthly lines failed to save.
- Shortening money detail to squeeze into a small field.
- Two charges for one confirmation.
- Full approval without a confirmed downpayment.

### Edge cases and what happens then
- Longest breakdown (all four extras + tuition, large peso figures) → still saves; space sized with headroom.
- Files that failed before the update → safe to retry after the update; no cleanup needed, no duplicate charge.
- Full-payment path (no monthly lines) → unchanged, still works.
- Future extras added to fees → still fit without another update.

## 7. Out of Scope
- Changing fee amounts, minimums, discounts, or breakdown wording.
- Changing approval, receipt numbering, or emails.
- Online payment.

## 8. Success Checks
- [ ] Installment downpayment on a previously failing file confirms once → receipt shows → file returns to Registrar as Pending.
- [ ] Month line keeps the full breakdown text (tuition + all extras readable).
- [ ] Double-tap shows already-confirmed with no second charge.
- [ ] Bulk retry confirms stuck files; any still-blocked file names its reason.

## 9. Open Questions (if any)
None — widen-and-keep-full-text confirmed as Option A on 2026-10-12.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: Cashier `payment` + `payments` (confirm flows); month breakdown read in `resources/views/portal/cashier/partials/payment-form.blade.php:91` via `term_label`.
- Likely areas of the codebase (from read-only inspection):
  - Table definition: `database/migrations/2026_10_12_000001_cashier_fee_rules_data.php:34` — `term_label` string 40 nullable; needs widening to 255 (new migration altering `ledger_lines.term_label`; Postgres widen is metadata-only).
  - Writers: `app/Services/FeeAssessmentService.php::assessLedger:136-140` + `switchPath` path + `bundledBreakdown:486-498` (`'Incl. ' + Tuition + misc parts`, observed ~95 chars with 4 extras) — no text change, only the column grows.
  - Model: `app/Models/LedgerLine.php` fillable includes `term_label` — no change needed.
  - Prior fix context: `app/Services/DownpaymentService.php` catch (diagnostics spec) surfaced `SQLSTATE[22001] ... value too long for type character varying(40)` on `insert into ledger_lines` — this spec fixes that throw.
- Data/records touched: `ledger_lines.term_label` (widen only; existing rows kept, previously failed files have no rows to repair since their transaction rolled back).
- Roles/permissions involved: cashier (confirm), IT admin (runs update); no permission change. Parent/related: `downpayment-failure-diagnostics.md` (Implemented), `fee-itemized-manager.md`, `cashier-installment-form-load-fix.md` (breakdown display).
- Peak-load note: widen runs once, fast on Postgres; confirm path unchanged otherwise; bulk retries paginated as today.

## 11. Approval
> Approved by user on 2026-10-12.
