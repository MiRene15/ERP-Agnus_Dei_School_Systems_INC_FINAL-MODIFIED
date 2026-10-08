# Spec: Discount Request Consistency

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Implemented**: 2026-10-08 — shared nine-list in all three request forms, five helper sentences state auto-post with Other named; verified by user read-through (all 4 checks pass).
- **Parent**: `cashier-discount-auto-apply.md` (follow-up — finishes its rollout across roles)

## 1. Why We Need This
The new discount flow (nine percentages, approval posts by itself) reached only the cashier hub form. The shared request page and the registrar hub still offer the old five picks, and five helper sentences still teach the old two-step flow. Staff following those words file requests the system contradicts.

## 2. Who Is Affected
* **Cashier + Registrar** — file identically from any of the three forms.
* **Directress** — reads truthful copy when approving.
* Untouched: parents, teachers, and the approval/posting behavior itself.

## 3. Business Flow: Today vs After
- **As-is**: Three New Request forms with two different pick lists; five sentences describing `approves → Cashier applies`.
- **To-be**: All three forms offer 5, 10, 20, 25, 30, 40, 50, 75, 100% and ESC/Honor/Sibling/Other; every sentence reads auto-post.
- **Preserved**: Approval step, posting math, audit, backlog Apply, permissions, routes — all unchanged.
- **Exceptions**: None — words and picks only.

## 4. How It Should Work
1. Cashier or registrar opens any New Request form → same nine picks, same four types.
2. Helper sentences state: request with proof → Directress approves → discount posts by itself.
3. Backlog Apply spots carry one honest note: pre-change items only.

## 5. Look & Feel (UX)
- Same forms, same buttons, same shells — picks row and helper sentences change, nothing moves.
- Key states: all unchanged.
- Plain words: `Request with proof (ESC, Honor, Sibling, Other) → Directress approves → discount posts by itself.`

## 6. Business Rules
### Must always be true
- All three New Request forms draw picks from the one shared nine-list.
- Type choices are ESC, Honor, Sibling, Other everywhere.
- Every flow sentence states auto-post; `Other` is always named.

### Must never happen
- No form may keep the old five picks or hide `Other`.
- No sentence may describe a cashier Apply step for new approvals.
- Approval, posting, audit, permissions, and routes must not change.

### Edge cases and what happens then
- In-flight 15% requests → keep working (peso-based, as before).
- Saved pages → refresh shows new picks/copy; nothing to reconcile.

## 7. Out of Scope
- Approval/posting logic; backlog behavior; new types or percentages beyond the nine.
- Any other role's screens.

## 8. Success Checks
- [ ] All three forms show exactly the nine picks with the four types.
- [ ] All five helper sentences state auto-post with `Other` named.
- [ ] Request → approve → auto-posted → stated at payment works from registrar filing too.
- [ ] Other roles see no change.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: shared request page, registrar hub request form, directress review pages, standalone Manage Discounts page (copy only).
- Likely areas (from code inspection): `discount-requests/index.blade.php:55` picks + `:12` copy; `registrar/requests.blade.php:68` picks + `:37` copy; `directress/discount-requests/index.blade.php:12` + `approvals.blade.php:31` copy; `cashier/discounts.blade.php:13` copy (backlog note); all picks loop `DiscountRequest::DISCOUNT_PERCENTS`.
- Data/records touched: none.
- Roles/permissions involved: cashier, registrar (file); directress (reads truthful copy). No permission change.

## 11. Approval
> Approved by user on 2026-10-08.
