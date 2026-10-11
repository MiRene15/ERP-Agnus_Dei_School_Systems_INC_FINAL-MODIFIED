# Spec: Enrollment Payment Gating — Payment Access

- **Status**: Implemented
- **Re-approved by**: user on 2026-10-12 (Pending-return amendment: Option A)
- **Implemented**: 2026-10-12 — downpayment confirm (single+bulk, receipt-first, idempotent) with Pending-return + receipt badge; forward-paid guard; full approval with section assign + enrollment + institution address + notice; paid badge/filter/queue; family checklist states; dashboard gate; acceptance test (6 checks). Marked per user with tests still running — re-open on any failure found in testing.
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-11 (superseded — Pending-return amendment pending re-approval)
- **Parent**: `enrollment-payment-gating.md` (child 2 of 3 — depends on child 1)
- **Revision**: 2026-10-12 — Option A (user): after downpayment the file returns to Registrar as Pending carrying a Downpayment-confirmed + receipt badge (no separate payment-confirmed status); section assigning happens at full approval.
- **Depends on**: child 1 (forwarded files) + #4 amounts (`cashier-fee-rules.md`: 1.5k on enrollment, −10% full-payment)

## 1. Why We Need This

Forwarded files pile up without a payment step today — admission jumps straight from Registrar approval to done, so nobody can prove the family paid before getting full access. This child adds the second half of the double gate: Cashier confirms the downpayment, Registrar grants full approval, the institution email goes out, and only then does the full dashboard unlock.

## 2. Who Is Affected

- **Cashier** — confirms downpayments on forwarded files; flags has-balance cases.
- **Registrar** — grants full approval on payment-confirmed files; enrollment is created here, not at review.
- **Families and students (primary)** — pay, receive the institution email at the student's address, gain the full dashboard.
- **IT Admin** — institution address provisioning. Principal/Directress read-only.

## 3. Business Flow: Today vs After

- **As-is**: no payment step exists in admission; approval creates the enrollment immediately.
- **To-be**: forwarded file (awaiting-downpayment) → Cashier confirms downpayment (single or bulk, has-balance flagged with clearance) → file returns to Registrar as Pending carrying a Downpayment-confirmed + receipt badge (queue filter surfaces paid files first) → Registrar assigns section (+ subjects) and grants Full Approval → enrollment created → `firstname.lastname@` provisioned and emailed to the student's address → full dashboard unlocks; before that, limited dashboard (checklist + bills) with any full-dashboard visit redirecting to the checklist.
- **Preserved**: amounts and receipts stay in Cashier's money flow (detail in #4); resendable emails; audit per hand-off; school-year locks.
- **Exceptions**: has-balance returnees need Cashier clearance added to the downpayment; partial/failed payments never advance the file.

## 4. How It Should Work

1. Cashier opens the downpayment queue (forwarded files, current year pre-filled, counts) → opens a file → takes the downpayment in person → taps Confirm Downpayment (receipt issued in the money flow).
2. File returns to the Registrar queue as Pending with a "Downpayment confirmed + receipt #" badge (queue filter surfaces paid files first); Forward is blocked once a receipt is attached — a paid file can only advance via Grant Full Approval.
3. Registrar opens paid Pending files → assigns section (+ subjects) → taps Grant Full Approval (single or bulk) → enrollment created → institution address provisioned → notice emailed to the student's email.
4. Family opens the student dashboard → full access; any earlier visit showed the limited checklist naming "Pay at Cashier" or "Awaiting full approval" as done/next.
5. Email collision (`firstname.lastname@` taken) → numbered suffix, family told plainly in the notice.

## 5. Look & Feel (UX)

- Where it lives: existing Cashier and Registrar queues — no new menu. One primary action each: `Confirm Downpayment`, `Grant Full Approval`.
- Family sees: limited dashboard checklist until approval, then "Account Ready" with the new address and sign-in guidance.
- Key states: awaiting-downpayment ("Pay ₱1.5k at Cashier to continue" — amount finalized in #4); paid-pending ("Downpayment received — awaiting full approval", shown as a badge + queue filter on the Pending file); approved ("Account Ready — sign in with firstname.lastname@"); denied (full dashboard redirects to checklist); receipt-pending (payment taken, receipt printing — file advances only after receipt exists).
- Plain labels, one confirmation per bulk action, double-taps advance one file once.

## 6. Business Rules

### Must always be true

- Downpayment confirmed before full approval, always with a receipt; no receipt, no advance.
- A paid file returns to Pending and advances only via Grant Full Approval — Forward is blocked once a receipt is attached.
- Full approval assigns the section (+ subjects) and creates the enrollment; nothing before it does either.
- One institution address per student; collisions numbered; the notice goes to the student's email from child 1.
- Double-taps (confirm/approve) advance each file exactly once.
- Amounts (1.5k, installment/−10% rules) are read from the fee setup in #4; this child never sets prices.

### Must never happen

- Full approval or institution email without a confirmed downpayment.
- Re-forward of a paid file (receipt attached).
- Full dashboard before full approval.
- Two active institution addresses for one student.
- A file advancing on a failed or refunded downpayment.

### Edge cases and what happens then

- Downpayment refunded/voided before approval → file drops back to awaiting-downpayment with history kept.
- Has-balance returnee → Cashier adds clearance to the same confirmation; Registrar sees "downpayment + clearance" before approving.
- Receipt printer fails → payment recorded first, file advances only when the receipt record exists; reprint from the receipt, never re-take payment.
- Student email changed after child 1 → Registrar updates it before approval; the notice goes to the updated address with history kept.
- School year locks between payment and approval → approval blocks with the message; payment record stands.

## 7. Out of Scope

- Setting prices, installments, discounts (all in #4).
- Calendar, holds, clearance-status removal (child 3).
- Online payment (in-person only in v1).
- Re-provisioning or renaming issued institution addresses (follow-up).

## 8. Success Checks

- [ ] Forwarded file → Cashier confirms downpayment with receipt → returns to Registrar as Pending with a Downpayment-confirmed + receipt badge (filterable).
- [ ] Paid Pending → Registrar assigns section + approves → enrollment exists, institution email arrives at the student's address, full dashboard opens.
- [ ] Before approval, full-dashboard visit redirects to the checklist naming the next step.
- [ ] Double-tap Confirm/Approve advances the file exactly once.
- [ ] Refunded downpayment before approval → file back to awaiting-downpayment.
- [ ] Name collision → numbered address, family informed in the notice.

## 9. Open Questions (if any)

None — downpayment-before-approval, receipt-required, enrollment-at-approval all confirmed. Prices finalize in #4.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: Cashier downpayment queue (new list reading forwarded files); Registrar approval list (payment-confirmed files, Grant Full Approval + bulk); student limited/full dashboard + checklist; institution-email notice (extends applicant-email guarantees).
- Likely areas of the codebase (from read-only inspection):
  - Cashier confirm writes a downpayment receipt in the existing money flow (receipt-first rule: the file advances only when the receipt record exists; reuse press protection so double-taps confirm once); has-balance clearance via `HoldService` flag on the same confirmation.
  - Full approval calls the enrollment-creation path today's approve uses (`RegistrarAdmissionController@approve` logic: gender/all-Verified/year-unlock checks + transaction creating enrollment + Approved By Registrar + confirmation email) — relocated here, not at review, plus section (+ subject) assignment in the same step; statuses advance awaiting-downpayment → Pending (paid, receipt attached, Forward blocked) → approved.
  - Provisioning creates `firstname.lastname@` (collision numbering) and emails credentials/notice to the child-1 student email; dashboard gate requires review + downpayment + full approval + verified email (extends `future-year-enrolled-dashboard.md`).
- Data/records touched: downpayment receipts, enrollments (created here), provisioned addresses, audit entries; no fee-schedule changes (that's #4).
- Roles/permissions involved: cashier (confirm), registrar (approve), IT admin (addresses); no permission-model change. Depends on child 1 + #4.
- Peak-load note: fee-due crowds — bulk confirm/approve with single confirmation, queues paginated with counts, receipt writes never block queue reads.

## 11. Approval

> Approved by user on 2026-10-11.
