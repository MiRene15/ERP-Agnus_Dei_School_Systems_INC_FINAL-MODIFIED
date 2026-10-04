# Spec: Cashier Projection Chart

- **Status**: Approved
- **Created**: 2026-10-04
- **Approved by**: user on 2026-10-04
- **Parent**: `cashier-reports-hub.md` (child 3 of 3 — needs toggle + cleanup)

## 1. Why We Need This
Cashiers see what's collected and what's owed, but never what's coming. Fee-week staffing and follow-up lists are guesswork. A pace-based outlook answers "are we on track?" at a glance.

## 2. Who Is Affected
* **Cashier** — only viewer; plans follow-ups and counters staffing.
* **Directress / principal** — untouched in v1; same numbers available on request later.

## 3. Business Flow: Today vs After
- **As-is**: outlook requires manual export math each time.
- **To-be**:
  1. Cashier opens the dashboard — strip shows Outstanding plus Estimate for the month.
  2. Cashier taps Projections in the left menu — monthly collected-vs-outstanding lines, same chart feel as the portal's other graphs.
- **Preserved**: balances, receipts, audit trail, reminders — all untouched; projection reads only.
- **Exceptions**: new school year with no collections yet → "No Collections Yet", never a fake line.

## 4. How It Should Work
1. Dashboard strip shows two numbers: Outstanding total and Estimate (expected month-end at current pace), labeled Estimate.
2. Projections page shows one line per series (Collected, Outstanding) across months with day/month labels.
3. Hover shows the month's numbers; legend hides a line.
4. Refresh updates strip and page together.

## 5. Look & Feel (UX)
- Dashboard strip sits with the existing overview cards; Projections page mirrors the Reports page shell (filters: school year). One primary action per view.
- Key states: default (current school year), empty ("No Collections Yet"), error ("unavailable — Refresh"), cashier-only menu + page.

## 6. Business Rules
### Must always be true
- Estimate = collected-so-far + daily pace × days left in month, never more than Outstanding.
- Pace uses real receipts of the current month only.
- Labeled Estimate everywhere it appears.

### Must never happen
- Estimate shown as a promise or used for clearance/enrollment decisions.
- Projection changes any balance, receipt, or reminder.
- Future dues invented without receipts behind them.

### Edge cases and what happens then
- No collections yet this month → "No Collections Yet", Estimate hidden.
- All settled → Outstanding ₱0.00, Estimate ₱0.00.
- Month-end passed → Estimate equals collected.

## 7. Out of Scope
- Per-student projections, parent reminders, directress copies, export for projections.

## 8. Success Checks
- [ ] Dashboard shows Outstanding + Estimate strip labeled Estimate.
- [ ] Projections opens from the left menu with monthly lines and follow-mouse boxes.
- [ ] With no collections, page reads "No Collections Yet" with no fake line.
- [ ] Non-cashiers see no menu and are denied by direct link.

## 9. Open Questions (if any)
None — pace method, wording, and placement confirmed 2026-10-04.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected: `portal/cashier/dashboard.blade.php` (+ `partials/dashboard-results.blade.php` strip), new `portal/cashier/projections.blade.php` + cashier sidebar menu item, `CashierController` (strip numbers + monthly series read from payments/ledgers).
- Chart reuses the portal's Chart.js line pattern (directress demographics / health Patterns): day/month labels, boxed follow-mouse tips, clickable legend.
- Data: read-only aggregates; no new tables (compute from existing receipts/balances).
- Roles: cashier only; others denied as today.

## 11. Approval
> Approved by user on 2026-10-04.
