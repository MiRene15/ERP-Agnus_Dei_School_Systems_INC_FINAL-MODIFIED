# Spec: Cashier Projections Menu Icon

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Implemented**: 2026-10-08 — swapped Projections sidebar SVG to trending-up (M13 7h8m0 0v8m0-8l-8 8-4-4-6 6), Reports untouched, verified visually by user.

## 1. Why We Need This
The cashier's left menu shows the same bars picture for both Projections and Reports. Cashiers hesitate for a second every time to tell them apart. A distinct up-and-to-the-right arrow for Projections makes each menu item scannable at a glance.

## 2. Who Is Affected
* **Cashier** — only viewer; finds Projections faster, fewer mis-taps.
* **IT Admin** — checks it once visually.
* Untouched: registrar, principal, directress, teachers, nurse, librarian, parents.

## 3. Business Flow: Today vs After
- **As-is**: Cashier opens the menu, sees two identical bars pictures, reads the labels to decide.
- **To-be**: Cashier opens the menu, sees ↗ for Projections and bars for Reports, taps Projections directly.
- **Preserved**: Menu order, labels, who can see the menu, balances, receipts, audit trail — all unchanged.
- **Exceptions**: None — this is a picture change only.

## 4. How It Should Work
1. Cashier signs in and opens the left menu.
2. The Projections row shows an up-and-to-the-right trending arrow.
3. Tapping it still opens the same Projections page with the same monthly chart.

## 5. Look & Feel (UX)
- Lives in the cashier left menu only. One primary action stays the same: tap Projections.
- Uses the same size, thickness, and color behavior as today, so default, selected, and dark screens all still look right.
- Key states: default menu, selected menu (highlighted), dark screen — arrow stays clear in each.
- Plain language: no label change; still says "Projections".

## 6. Business Rules
### Must always be true
- The Projections picture is different from the Reports picture.
- The new picture follows the menu color in all screens (light, dark, selected).
- Tapping Projections opens the same page as today.

### Must never happen
- Menu order, labels, or who can see the menu must not change.
- Reports picture, Projections page, chart, figures, or filters must not change.
- No second way to do the same thing is introduced.

### Edge cases and what happens then
- Saved page in browser shows old picture → refresh shows the new one; no action needed.
- Small screen / collapsed menu → arrow stays clear at small size.

## 7. Out of Scope
- Reports icon, Projections page, chart, figures, filters, wording.
- Any other role's menu.

## 8. Success Checks
- [ ] Projections shows an up-and-to-the-right arrow, Reports still shows bars.
- [ ] Tapping Projections still opens the monthly chart page.
- [ ] Arrow is clear in default, selected, and dark screens.
- [ ] Non-cashiers see no change.

## 9. Open Questions (if any)
None — zigzag trending-up confirmed by you on 2026-10-08.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screen: cashier left menu only.
- Likely area: `resources/views/portal/partials/sidebar-cashier.blade.php` Projections link (~line 10 `<svg>` path only); keep `sidebar-icon` class, 24 view, current color, width 2. New path is the trending-up shape agreed in chat.
- Do not touch: Reports link in same file, `portal/cashier/projections.blade.php`, chart script, data service.
- Roles/permissions involved: cashier-only menu item; no permission change.

## 11. Approval
> Approved by user on 2026-10-08.
