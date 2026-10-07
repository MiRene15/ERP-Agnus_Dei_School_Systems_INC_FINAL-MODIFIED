# Spec: Cashier Financial View Modal

- **Status**: Implemented (2026-10-07 — fonts, financial modal, payment modal + sizing pass; all checks confirmed by user)
- **Created**: 2026-10-07
- **Approved by**: user on 2026-10-07 (covering fonts + financial modal; change #10 pending re-approval)

## 1. Why We Need This

Checking a student's ledger costs the cashier a full page navigation away from the payments queue and back — painful on fee due dates when every second counts. Separately, receipt (`RCP-`) and AR (`AR-`) numbers render in typewriter-style mono font inside the financial tables while everything around them uses the regular table font, so the numbers look out of place.

This spec puts the full financial record inside a floating modal (no more leaving the queue) and normalizes the receipt/AR number fonts to match their tables.

## 2. Who Is Affected

- **Cashier** — the only role whose screens change. Faster ledger checks, consistent number fonts.
- **Registrar** — no visible change (financial view is cashier-only).
- **Students/parents** — no visible change.
- **IT admin** — no new tables, no new permissions.

## 3. Business Flow: Today vs After

- **As-is**: cashier clicks "Financial View" on a student row → full page loads → reads ledger → clicks back to return to the queue. Receipt/AR numbers show in mono font in two tables.
- **To-be**:
  1. Cashier clicks "Financial View" on a student row (or on the payment page) → the same ledger opens in a floating modal over the queue.
  2. Cashier reads balances, closes the modal — the queue is exactly where they left it, search text intact.
  3. Receipt/AR numbers render in the regular table font everywhere on screen.
  4. "Process Payment" buttons open the full payment form in a second modal stacked above (financial modal stays behind where applicable) — receiving money without leaving the queue.
- **Preserved**: the standalone Financial View page still opens directly via URL (deep links, printing). Payments, voids, discounts, and graduation-fee marking work exactly as today. Locked years still gate all writes. Audit trail unchanged.
- **Exceptions**: void inside the modal confirms and refreshes modal content in place. Receipt printing always opens a new tab. A student with no payments sees fee schedules, balances, and a "no payments yet" note — never a blank box.

## 4. How It Should Work

1. Cashier searches a student on Process Payments and clicks "Financial View" on their row — a centered modal opens titled "Financial View — {student name}".
2. The modal shows the same sections as the page today, in the same order: student info, fee schedules, payment history with receipt/AR numbers, discounts, library fees, graduation fees, balances.
3. Cashier clicks a receipt-print link inside the modal — it opens in a new tab; the modal stays open underneath.
4. Cashier clicks void inside the modal — the usual confirmation appears; on confirm the modal content refreshes showing the reversal.
5. Cashier clicks "Process Payment" inside the modal — goes to the payment page (full navigation, modal closes).
6. Cashier closes the modal (× button, backdrop click, or Escape) — the payments queue is untouched, search text and results intact.
7. Everywhere on screen, RCP/AR numbers use the regular table font with dark-mode support.
8. Cashier clicks any "Process Payment" button (payments row, financial modal, payment page header) — the full payment form (discount math, tendered/change, receipt upload) opens in a payment modal above everything.
9. Cashier submits inside the payment modal — on success the payment modal closes, the financial modal behind refreshes showing the new payment, and a success notice with a Print Receipt link (new tab) appears. The queue stays intact behind everything.

## 5. Look & Feel (UX)

- **Modal**: centered floating card (same pattern as the withdrawal modal), small width with roomy body padding, backdrop blur, × button + backdrop-click + Escape to close, body scrolls internally for long ledgers, max height 90% of viewport so it never leaves the screen.
- **Content**: identical to the standalone page — same sections, same order, same actions. The year filter inside the modal reloads modal content only (no full-page jump).
- **Fonts**: RCP/AR numbers drop `font-mono`, matching surrounding cells (`text-gray-600` body style with dark variant). Printed 72mm receipt untouched.
- **Empty states**: "No payments recorded yet" with balances and schedules still visible.
- **What the cashier sees first**: student name + outstanding balance at the top, then the sections.
- **Payment modal**: second layer above the financial modal (higher z-index), same shell pattern. Full payment form inside — identical fields, math, and confirmations. Receipt file upload works. Closes only via its own ×/backdrop/Escape or after success.

## 6. Business Rules

### Must always be true
- The modal shows exactly what the standalone page shows — same sections, same figures, same order.
- RCP/AR numbers on screen use the regular table font, in light and dark mode.
- The printed 72mm receipt format is unchanged.
- Closing the modal never loses the cashier's queue state (search text, results, scroll).
- The modal is read-only except for the existing actions (void, print, process payment, mark graduation paid), which follow their existing rules.
- A payment submitted from the payment modal follows the exact same validation, confirmations, locked-year rules, and idempotency as the standalone payment page.

### Must never happen
- A void or payment processed from the modal bypasses existing confirmations or locked-year rules.
- The modal shows stale figures after a void — content refreshes in place.
- A full-page navigation fires from inside the modal except "Process Payment" (deliberate handoff).
- Any number on screen renders in mono font after this change (except the printed receipt, which keeps its printer styling).

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Void clicked inside modal | Usual confirmation; on confirm, modal content refreshes showing the reversal; modal stays open |
| Receipt print clicked inside modal | Opens in a new tab; modal stays open underneath |
| Student with no payments | Fee schedules + balances + "no payments yet" note; never blank |
| Locked school year | Modal opens read-only; writes refused exactly as on the page |
| Data changes while modal open | Closing and reopening shows fresh data |
| Year filter changed inside modal | Modal content reloads only; queue untouched |
| Payment submitted from payment modal | Payment modal closes; financial modal refreshes with success notice + Print Receipt link (new tab); queue intact |
| Payment modal closed without paying | Nothing happens — no record created, financial modal untouched |
| Receipt upload inside payment modal | Uploads with the submission; same file rules as the page |

## 7. Out of Scope

- Printed 72mm receipt restyling — thermal-printer format stays exactly as-is
- Online payments (GCash/card)
- Installment plan management
- Payment reminders UI
- Changing what the ledger contains — same data, new container

## 8. Success Checks

- [ ] RCP numbers display in regular table font (no mono) in the financial view tables
- [ ] AR numbers display in regular table font (no mono) in the financial view tables
- [ ] Receipt/AR numbers look identical to surrounding cells, in light and dark mode
- [ ] Printed 72mm receipt is unchanged in appearance
- [ ] Each student row in Process Payments results has a working Financial View button
- [ ] Individual payment page has a working Financial View button
- [ ] Modal shows the same sections, figures, and actions as the standalone page
- [ ] Void inside the modal processes and refreshes the modal content
- [ ] Standalone Financial View page still opens directly via URL
- [ ] "Process Payment" opens the full payment form in a stacked modal from rows, financial modal, and payment page
- [ ] Successful modal payment closes the payment modal, refreshes the financial modal, and offers Print Receipt
- [ ] Standalone payment page still works directly via URL

## 9. Open Questions

None — all decisions made during interview.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- **Font spots (remove `font-mono`):** `resources/views/portal/cashier/partials/student-financial-results.blade.php` line 161 (receipt cell) and line 252 (receipt + AR cells in the compact history table). Replace with the surrounding cell style. Check `payment.blade.php` line 298 ("Receipt: … | AR: …" already regular — leave as-is). Printed receipt `partials/receipt-print.blade.php` — do not touch.
- **Modal content source:** `CashierController@studentFinancial` (line 465) already serves both full page and AJAX (`?ajax=1` → `partials/student-financial-results`). The modal reuses the AJAX path — no new data endpoint needed.
- **Modal shell:** new shared piece following the `withdrawal-modal` precedent (teleport to body on open, backdrop blur, × + backdrop-click + Escape, `max-h-[90vh]` scroll). The payment-year `<select>` inside the partial currently does full-page navigation (`window.location.href`) — must be converted to modal-content reload when rendered inside the modal (pass a flag or detect modal context).
- **Buttons:** payments results rows (`payments.blade.php` lines 87–89, client-rendered from JSON) — the "Financial View" link becomes a modal opener carrying the student id; `payment.blade.php` individual page gets the same button. Standalone page and route stay untouched.
- **In-modal writes:** void/print/mark-paid forms post normally; responses should refresh modal content (re-fetch AJAX partial) rather than full-page redirect. Locked-year and idempotency behavior unchanged.
- **Data/records touched:** none — read-only shell change plus font classes. No migration.
- **Roles/permissions involved:** cashier only (existing `role:3` routes reused).

## 11. Approval

> Approved by user on 2026-10-07.
