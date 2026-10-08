# Spec: Cashier Dashboard Receivables Wording

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Implemented**: 2026-10-08 — helper swapped to `View by Date Range` (line 56 only), verified visually by user alongside card-order move; shared commit with card-order spec.

## 1. Why We Need This
The two report cards on the cashier dashboard speak in different voices. One says what to do, the other says what is owed. Matching helpers makes the dashboard calmer to scan.

## 2. Who Is Affected
* **Cashier** — only reader; taps the right report without re-reading.
* **IT Admin** — checks it once visually.
* Untouched: directress, principal, registrar, teachers, nurse, librarian, parents.

## 3. Business Flow: Today vs After
- **As-is**: Cashier signs in, sees `Collections Report → View by Date Range` and `Receivables Report → Money still owed`, taps one to open Reports.
- **To-be**: Both cards read `View by Date Range`. Tapping opens the same tabs as today.
- **Preserved**: Six cards, tab destinations, figures, filters, balances, receipts, audit records — all unchanged.
- **Exceptions**: None — wording change only.

## 4. How It Should Work
1. Cashier signs in and sees the six cards.
2. The Receivables card shows `Receivables Report` with `View by Date Range` underneath.
3. Tapping it opens Reports on the Receivables tab, same as today.

## 5. Look & Feel (UX)
- Lives on the cashier dashboard only. One job per card: open its tab.
- Order, colors, icons stay exactly as today; only the helper sentence under Receivables changes.
- Key states: default six cards, zero-day numbers, error, cashier-only — helper reads the same in each.
- Plain language: titles still say which report it is; helpers now both describe the action.

## 6. Business Rules
### Must always be true
- The Receivables helper reads exactly `View by Date Range`.
- The Collections helper still reads `View by Date Range`.
- Tapping Receivables still opens Reports on the Receivables tab; Collections still opens Collections tab.
- Titles `Collections Report` and `Receivables Report` stay.

### Must never happen
- The link behind either card must not change.
- Figures, tabs, filters, exports, or who sees the dashboard must not change.
- A date picker, Apply button, or background reload must not return to the dashboard.

### Edge cases and what happens then
- Saved page shows old line → refresh shows the new one; nothing to reconcile.
- Cashier taps the wrong card at fee-due rush → same Reports page, other tab is one click away.

## 7. Out of Scope
- Reports pages themselves, Projections, menus, other dashboards.
- Figures, filters, exports, approvals.

## 8. Success Checks
- [ ] Receivables card reads `View by Date Range` exactly.
- [ ] Collections card still reads `View by Date Range` exactly.
- [ ] Receivables card still opens Reports on the Receivables tab.
- [ ] Collections card still opens Reports on the Collections tab.
- [ ] No date picker on the dashboard; other four cards unchanged.

## 9. Open Questions (if any)
None — exact swap with identical helpers accepted by you on 2026-10-08.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screen: cashier dashboard only.
- Likely area: `resources/views/portal/cashier/dashboard.blade.php` Receivables card helper (~line 56 bold line only).
- Do not touch: Collections card, links, tabs, figures, any other file.
- Data/records touched: none.
- Roles/permissions involved: cashier-only page; no permission change.
- Follow-up to: `cashier-dashboard-simplification.md` (Implemented, owns old line in §12) — left untouched.

## 11. Approval
> Approved by user on 2026-10-08.
