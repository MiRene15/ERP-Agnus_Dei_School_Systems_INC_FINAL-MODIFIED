# Spec: Cashier Dashboard Card Order

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Implemented**: 2026-10-08 — Receivables card moved 4th to 6th (Collection, Receipts, Collections, Discounts, Refunds, Receivables last), contents unchanged, verified visually by user; shared commit with receivables-wording spec.

## 1. Why We Need This
The cashier opens the day with today's money, then clears waiting work, then checks what is still owed. Putting Receivables last matches that morning order.

## 2. Who Is Affected
* **Cashier** — only reader; scans top-to-bottom in work order.
* **IT Admin** — checks it once visually.
* Untouched: directress, principal, registrar, teachers, nurse, librarian, parents.

## 3. Business Flow: Today vs After
- **As-is**: Today's Collection → Receipts Today → Collections Report → Receivables Report → Discounts → Refunds.
- **To-be**: Today's Collection → Receipts Today → Collections Report → Discounts → Refunds → Receivables Report last.
- **Preserved**: Six cards, same links and tabs, same figures, same wording (`View by Date Range` both), balances and audit records untouched.
- **Exceptions**: None — order change only.

## 4. How It Should Work
1. Cashier signs in and sees the six cards in the new order.
2. Tapping any card opens the same place as today.
3. On a phone the same order stacks top-to-bottom.

## 5. Look & Feel (UX)
- Lives on the cashier dashboard only. No new primary action — see today's position, then move through queues.
- Same grid, colors, icons, titles, helpers — only sequence moves.
- Key states: default six cards, zero-day numbers, error, cashier-only — order stays the same in each.
- What the cashier sees first: Today's Collection, top-left on desktop and top on mobile.

## 6. Business Rules
### Must always be true
- The order is exactly: Today's Collection, Receipts Issued Today, Collections Report, Discounts to Apply, Refunds to Release, Receivables Report last.
- Receivables still opens Reports on the Receivables tab; Collections still opens Collections tab.
- Both report helpers still read `View by Date Range`.

### Must never happen
- Titles, helpers, links, figures, or who sees the page must not change.
- A date picker, Apply button, or background reload must not return.
- Any other dashboard must not be reordered.

### Edge cases and what happens then
- Saved page shows old order → refresh shows the new one; nothing to reconcile.
- Small screen → same order stacks; no extra scrolling fix needed.

## 7. Out of Scope
- Wording, figures, tabs, filters, exports, approvals.
- Reports pages, Projections, other roles.

## 8. Success Checks
- [ ] Cards read top-to-bottom: Collection, Receipts, Collections, Discounts, Refunds, Receivables last.
- [ ] Receivables still opens the Receivables tab; Collections still opens Collections tab.
- [ ] Both helpers still read `View by Date Range`; titles unchanged.
- [ ] No date picker; other dashboards unchanged.

## 9. Open Questions (if any)
None — exact order confirmed by you on 2026-10-08. Note: `cashier-dashboard-receivables-wording.md` Slice 2 verification on this same file is still open; close it before implementing this move.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screen: cashier dashboard only.
- Likely area: `resources/views/portal/cashier/dashboard.blade.php` — move the Receivables card block (indigo, `view=receivables`) from 4th to 6th; keep all text, links, classes.
- Do not touch: card contents, links, tabs, figures, any other file.
- Data/records touched: none.
- Roles/permissions involved: cashier-only page; no permission change.
- Follow-up to: `cashier-dashboard-simplification.md` (Implemented) and `cashier-dashboard-receivables-wording.md` (Approved, uncommitted) — both left untouched.

## 11. Approval
> Approved by user on 2026-10-08.
