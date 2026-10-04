# Spec: Cashier Reports Toggle

- **Status**: Implemented (2026-10-04 — pill toggle with address persistence; all 3 checks pass)
- **Created**: 2026-10-04
- **Approved by**: user on 2026-10-04
- **Parent**: `cashier-reports-hub.md` (child 1 of 3 — build first)

## 1. Why We Need This
Two tabs split one job. Cashiers flip between money-in and money-owed all day during fee week; one switch keeps them on a single screen with nothing to relearn.

## 2. Who Is Affected
* **Cashier** — only viewer; same info, one switch.
* **Directress / principal, registrar, parents** — untouched; their copies and statements don't change.

## 3. Business Flow: Today vs After
- **As-is**: Reports page shows "Collections Report" and "Receivables Report" underline tabs swapping the content below.
- **To-be**:
  1. Cashier opens Reports — a single [Collections | Receivables] pill toggle sits where the tabs were, defaulting to Collections.
  2. Cashier taps Receivables — the report area swaps; each side keeps its own From/To, Generate, and Export.
  3. Cashier reloads or goes back — the chosen side is kept (page address remembers it).
- **Preserved**: both views' filters, exports, audit trail, and the date-limits pairing/guards.
- **Exceptions**: refunds/corrections display exactly as today on both sides.

## 4. How It Should Work
1. Reports opens on Collections with current month pre-filled.
2. One tap flips to Receivables with its own filters intact.
3. Flipping back restores Collections exactly as left, without re-picking dates.
4. Export downloads the side currently shown, for its shown range.

## 5. Look & Feel (UX)
- Where it lives: top of the Reports page, replacing the tab row. One job: switch sides.
- Key states: default Collections; empty per existing views ("No payments found…", "All settled"); error keeps the last good side plus Refresh; cashier-only as today.
- Same navy pill style as the rest of the page; works in dark mode and on narrow screens.

## 6. Business Rules
### Must always be true
- Toggle state survives reload and back-button.
- Each side keeps its own dates, results, and export.
- Collected and owed totals never mix.

### Must never happen
- Switching sides loses the other side's picked dates.
- Export downloads the hidden side's data.
- Non-cashiers gain access.

### Edge cases and what happens then
- Mid-load flip → latest tap wins, no mixed content.
- Export with no rows → empty file with headers, as today.

## 7. Out of Scope
- Receivables redesign (child 2), projection chart (child 3), directress copies, reminders.

## 8. Success Checks
- [ ] Open Reports — Collections shows with current month; one tap shows Receivables with its own filters.
- [ ] Reload the page on Receivables — still on Receivables.
- [ ] Export on each side matches the shown side and range.

## 9. Open Questions (if any)
None — pill toggle, same page, per-side filters confirmed 2026-10-04.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: `portal/cashier/reports.blade.php` only (tab row + `x-data` state).
- Likely areas: replace tab buttons with a segmented control bound to the same `tab` state; persist via query value (e.g. `?view=receivables`) read on load; leave both `ajaxTable` blocks and exports untouched.
- Data touched: none new — same two views, same ranges.
- Roles: cashier only, as today.

## 11. Approval
> Approved by user on 2026-10-04. Implemented 2026-10-04: pill toggle with `?view=` persistence; user confirmed all 3 acceptance checks pass.
