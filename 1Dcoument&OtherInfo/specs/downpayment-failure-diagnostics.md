# Spec: Downpayment Failure Diagnostics

- **Status**: Implemented
- **Created**: 2026-10-12
- **Approved by**: user on 2026-10-12
- **Implemented**: 2026-10-12 — truthful downpayment errors (service logs full context + safe-to-retry wording, bulk names first blocker, bulk error box visible).

## 1. Why We Need This
Cashiers take cash in person, tap Confirm Downpayment, and get "processing failed, please try again" with no reason. They don't know if money was recorded or if it's safe to tap again. Files stay stuck waiting for payment and the Registrar queue stays empty. This fix makes the failure tell the truth so the Cashier always knows what to do next.

## 2. Who Is Affected
- **Cashier (primary)** — gets a clear reason plus safe-to-retry or do-not-retake guidance; no more guessing.
- **Registrar** — stuck files start moving again; no change to how approvals work.
- **Families and students** — stop looping on "Pay at Cashier"; next step is clear.
- **IT Admin** — full error detail in logs for disputes and audit.

## 3. Business Flow: Today vs After
- **As-is**: forwarded file → Cashier taps Confirm → generic "processing failed" → file stays waiting, Cashier unsure whether to retake cash.
- **To-be**: forwarded file → Cashier taps Confirm → success path unchanged (receipt + file returns to Registrar as Pending with receipt badge); fail path names the reason in plain words and says if safe to retry or already charged.
- **Preserved**: no receipt means no advance; double-taps record money once; every attempt keeps history; locked school years still block.
- **Exceptions**: failed payments never advance the file; voided receipts before approval still drop the file back to waiting with history kept.

## 4. How It Should Work
1. Cashier opens the downpayment queue → opens a file → enters plan and amount → taps Confirm Downpayment.
2. If it works: receipt shows, file returns to Registrar as Pending with receipt number badge (today's behavior, unchanged).
3. If it fails: screen shows the real reason in plain words (for example minimum amount needed, missing fee setup, locked year) plus one next step: "No charge made — safe to retry" or "Already confirmed (RCP-xxx) — do not take payment again."
4. Bulk confirm: each file goes through the same checks; confirmed files advance, blocked files stay behind with their own reason listed; one summary names the first blocker.

## 5. Look & Feel (UX)
- Where it lives: existing Cashier downpayment list and payment screen — no new menu. One primary action stays `Confirm Downpayment`.
- Cashier sees first: receipt number on success; reason + next step on failure; already-confirmed badge on double-tap.
- Key states: default form (plan + amount pre-filled, current year), success (receipt + returned-to-Registrar note), error-with-reason (what happened + what to do), already-confirmed (receipt shown, no new charge), permission-denied (cashier-only).
- Plain labels, fewer clicks, no code words on screen.

## 6. Business Rules
### Must always be true
- A file advances only when a receipt record exists.
- Double-taps record money exactly once and show the existing receipt.
- Failed attempts never move the file and always name the reason.
- Every confirm, retry, and void keeps history with who did it.

### Must never happen
- A file advancing on a failed payment.
- Taking payment a second time when a receipt already exists.
- Showing a raw system error to the Cashier with no next step.
- Full approval or family dashboard unlock without a confirmed downpayment.

### Edge cases and what happens then
- Double-tap after success → "Already confirmed (RCP-xxx). No new charge made."
- Amount below minimum → block and name the minimum figure and what it covers; safe to retry with correct amount.
- No fee setup for grade/year → "Ask the Directress or Registrar to set fees first"; safe to retry later.
- School year locked → block with year named; payment record stands, approval blocked separately.
- Receipt printer fails after money recorded → file still advances (receipt exists); reprint from receipt, never retake.

## 7. Out of Scope
- Changing fee amounts, minimums, or discount rules.
- Changing receipt numbering.
- Changing Registrar approval, section assignment, or family email steps.
- Online payment.
- The deeper root-cause fix itself — that follows as a second small spec once logs reveal the exact throw.

## 8. Success Checks
- [ ] Valid downpayment confirms once → receipt shows → file returns to Registrar as Pending with receipt badge.
- [ ] Double-tap shows already-confirmed with receipt and makes no second charge.
- [ ] Failing case names the real reason plus safe-to-retry or do-not-retake; failed file never advances.
- [ ] Bulk names the blocker per file; nothing half-confirmed.

## 9. Open Questions (if any)
- Exact failing grade/year/plan/amount and the `Downpayment confirmation failed:` log line — needed to scope the follow-up root-fix spec. Nothing here is guessed; the follow-up waits for that line.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: Cashier `payments` + `payment` (single confirm), bulk confirm modal, Registrar pending list badge (read-only, unchanged).
- Likely areas of the codebase (from read-only inspection):
  - `app/Services/DownpaymentService.php::confirm` catch block (lines ~230-244) — today maps unknown throws to generic text; keep friendly UI text but log `getMessage + trace + student_id/admission_id/grade/year/plan/amount/ledger_id` and pass through named reasons; never change transaction boundaries in this slice.
  - `app/Http/Controllers/Portal/CashierController.php::confirmDownpayment + bulkConfirmDownpayments` — surface `message + receipt_or_not + safe-to-retry flag` for single + per-file JSON/redirect; first-blocker summary for bulk.
  - Money path touched only for logging (no math change): `FeeAssessmentService::assessLedger/floorFor/allocatePayment`, `Payment::create + generateArNumber (AR-YYYY-xxxx)`, `StudentLedger + ledgerLines`, `LedgerService::refreshClearance`, helpers `log_activity + school_year_locked`.
  - Routes: `POST /cashier/payment/{student}/downpayment`, `POST /cashier/payment/bulk-downpayment` (cashier role_id 3 via `ProcessCashierPaymentRequest` / `BulkDownpaymentRequest`).
- Data/records touched: read-only in this slice except richer log + message; `admissions.status/downpayment_receipt/downpayment_paid_at`, `payments.receipt_number/ar_number`, `student_ledgers/ledger_lines`, `fee_schedules`, `activity_log` preserved.
- Roles/permissions involved: cashier (confirm), registrar (read pending-return), IT admin (logs); no permission-model change. Parent: `enrollment-payment-gating-payment-access.md` (Implemented, untouched).
- Peak-load note: no extra queries on queue reads; richer error path only.

## 11. Approval
> Approved by user on 2026-10-12.
