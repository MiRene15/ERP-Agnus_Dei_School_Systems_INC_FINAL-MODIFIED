# Spec: Cashier Hub Tab Navigation

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Implemented**: 2026-10-08 — row Request flips to hub Discount Requests toggle with student preselected, standalone + dashboard cards land on hub tabs; verified click-through by user (all 5 checks pass).

## 1. Why We Need This
The cashier works inside one queue screen with three toggles, but three buttons throw her out to full pages — she loses her search, her tab, and her place, then must find her way back. Keeping every action on the toggles means she never leaves the queue.

## 2. Who Is Affected
* **Cashier** — only user; stays in place, student pre-picked, fewer clicks.
* Untouched: directress, registrar, every other role.

## 3. Business Flow: Today vs After
- **As-is**: Row-level Request opens the standalone request page; standalone Manage Discounts links out again; dashboard Discounts/Refunds cards open standalone full pages.
- **To-be**:
  1. Row-level Request flips to the Discount Requests toggle with that student already picked.
  2. Standalone Manage Discounts links into the hub's Discount Requests toggle.
  3. Dashboard Discounts card opens the hub on Discounts; Refunds card opens the hub on Refunds.
- **Preserved**: Every destination behaves exactly as today (same forms, same postings, same approvals); standalone pages stay valid addresses, just unlinked from these three spots.
- **Exceptions**: None — navigation only.

## 4. How It Should Work
1. Cashier clicks Request on a student row → the Discount Requests toggle opens with that student already selected → she picks a percent and sends.
2. Cashier clicks Request Discount on the standalone page → the hub's Discount Requests toggle opens.
3. Cashier taps a dashboard card → the hub opens on its matching toggle.

## 5. Look & Feel (UX)
- Same toggles, same badges, same button words and styling — only destinations change.
- Row Request looks like the same link, behaves as an in-place flip with preselect.
- Key states: default tabs, preselected student (highlighted in the dropdown), dark mode unchanged.
- Fewest clicks: row → tab with student picked is one click where two pages used to be.

## 6. Business Rules
### Must always be true
- Row Request always opens the Discount Requests toggle with that exact student pre-picked.
- Dashboard cards always land on their matching hub toggle.
- The request form, postings, approvals, and queue counts behave exactly as today.

### Must never happen
- No button in this flow may load a standalone full page again.
- Preselect must never pick the wrong student (row id and dropdown value must match).
- Tab badges, toggle behavior, and dark styling must not change.

### Edge cases and what happens then
- Student already has an open request → tab opens with student picked; submitting still reports the existing open-request message as today.
- Saved/bookmarked standalone addresses → still open (routes stay valid); only the three links change.
- Small screen → toggles wrap as today; no layout change.

## 7. Out of Scope
- Request/posting/approval logic; deleting or rerouting the standalone pages; any other role.

## 8. Success Checks
- [ ] Row Request flips to the Discount Requests toggle in place (no page load) with the right student picked.
- [ ] Standalone Request Discount opens the hub's Discount Requests toggle.
- [ ] Dashboard Discounts card opens hub on Discounts; Refunds card opens hub on Refunds.
- [ ] Request flow, postings, badges, and dark mode work exactly as before.
- [ ] Other roles see no change.

## 9. Open Questions (if any)
None — full set with preselect confirmed by you on 2026-10-08.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: cashier hub (`requests.blade.php` + `discounts-results` partial), standalone Manage Discounts page, cashier dashboard cards.
- Likely areas (from code inspection): `partials/discounts-results.blade.php:51` row link becomes an in-scope tab flip that sets the `ledger-select` dropdown to the row's ledger id (same-page Alpine scope + plain script, matching the existing `setDiscountPreset` pattern); `discounts.blade.php:15` links to the hub with the requests view; `dashboard.blade.php:49,60` cards link to the hub with their matching views; hub already reads the `view` address piece (`requests.blade.php:22` accepts discounts/requests/refunds).
- Data/records touched: none.
- Roles/permissions involved: cashier only; no permission change.

## 11. Approval
> Approved by user on 2026-10-08.
