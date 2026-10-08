# Spec: Cashier Financial Modal Width

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Implemented**: 2026-10-08 — popup max width 720px to 880px (one class, line 3); verified visually by user (Print/Void visible, behavior unchanged).

## 1. Why We Need This
The Financial View popup is narrower than its receipts table, so Print and Void slide out of sight and the cashier must scroll sideways to reach them. A slightly wider popup keeps both buttons visible.

## 2. Who Is Affected
* **Cashier** — only viewer; prints and voids without sideways scrolling.
* Untouched: every other role (popup is cashier-only).

## 3. Business Flow: Today vs After
- **As-is**: Cashier opens Financial View, scrolls sideways to find Print/Void.
- **To-be**: Cashier opens Financial View, Print/Void are visible with no sideways scroll.
- **Preserved**: Opening, closing, loading, retry, year filter, receipt printing and voiding themselves — all unchanged.
- **Exceptions**: None — width change only.

## 4. How It Should Work
1. Cashier opens Financial View for any family.
2. The popup renders slightly wider; the receipts table plus Print/Void fit without horizontal scrolling on a desktop screen.

## 5. Look & Feel (UX)
- Same popup, same everything — only the maximum width grows from 720 to 880 pixels.
- Height, vertical scrolling, backdrop, close ×, dark mode — all unchanged.
- Phones: unchanged (full-width-with-margin behavior still governs small screens).

## 6. Business Rules
### Must always be true
- The popup's maximum width is 880 pixels on desktop-size screens.
- Print and Void are reachable without horizontal scrolling on a desktop screen.
- Small screens still show the popup with side margins, never cut off.

### Must never happen
- Modal behavior (open/close/loading/retry/filter) must not change.
- Receipt printing, voiding, balances, or audit records must not change.

### Edge cases and what happens then
- Narrow laptop (1024px) → 880 popup + margins still fits; no change needed.
- Phone → full-width-with-margin as today; horizontal table scroll may remain there as today.

## 7. Out of Scope
- Table redesign, button redesign, void/print logic, any other popup.

## 8. Success Checks
- [ ] Financial View on desktop shows Print and Void with no horizontal scrolling.
- [ ] Popup opens, loads, retries, filters year, and closes exactly as before.
- [ ] Phone view is unchanged (margins intact, nothing cut off).

## 9. Open Questions (if any)
None — 880px confirmed by you on 2026-10-08.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screen: cashier Financial View popup only.
- Likely area: `resources/views/portal/cashier/partials/financial-modal.blade.php` shell line (~line 3 `max-w-[720px]` → `max-w-[880px]`); nothing else in the file.
- Data/records touched: none.
- Roles/permissions involved: cashier-only popup; no permission change.

## 11. Approval
> Approved by user on 2026-10-08.
