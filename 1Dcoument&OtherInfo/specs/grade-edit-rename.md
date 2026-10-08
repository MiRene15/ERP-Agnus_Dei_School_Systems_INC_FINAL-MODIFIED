# Spec: Grade Edit Request Rename

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Revision**: 2026-10-08 — label corrected to `Grade Edit Request` across all strings. Re-approved by user on 2026-10-08.
- **Implemented**: 2026-10-08 — sidebar, page title/breadcrumb, and dashboard card renamed; verified visually by user (all 3 checks pass).
- **Parent**: `teacher-gradebook-overhaul.md` (child 4 of 4 — depends on parent only)

## 1. Why We Need This
"Grade Corrections" sounds like a penalty box. "Grade Edit Request" says what the teacher actually does there — open a locked grade, fix it, re-submit. Same flow, friendlier door.

## 2. Who Is Affected
* **Teachers** — only readers; see Grade Edit Request in menu, title, and dashboard card.
* Untouched: registrar, principal (their review wording stays), and the unlock rules themselves.

## 3. Business Flow: Today vs After
- **As-is**: Menu, page title, and dashboard card read Grade Corrections; flow is request unlock → approve → edit → re-submit.
- **To-be**: Those three read Grade Edit Request; flow is byte-identical.
- **Preserved**: Routes, unlock approvals, statuses, audit — everything behaves exactly as today.
- **Exceptions**: None — labels only.

## 4. How It Should Work
1. Teacher opens the menu → sees Grade Edit Request → opens it → page title reads Grade Edit Request.
2. Dashboard card reads Grade Edit Request and opens the same page.
3. Unlock requests work exactly as before.

## 5. Look & Feel (UX)
- Same sidebar, same page, same card — three strings change, nothing else moves.
- Key states: all unchanged.
- Plain words: `Grade Edit Request` in all three spots.

## 6. Business Rules
### Must always be true
- Sidebar label, page breadcrumb + title, and dashboard card all read exactly `Grade Edit Request`.
- Every link still opens the same unlock page as today.

### Must never happen
- Route names, addresses, unlock logic, statuses, or audit must not change.
- Registrar/principal review wording must not change in this child.
- No other label on any screen may change.

### Edge cases and what happens then
- Saved bookmarks → same addresses, new labels; nothing to reconcile.

## 7. Out of Scope
- Unlock logic, batch entry, formula, search, attendance (children 1–3); registrar/principal wording; other roles.

## 8. Success Checks
- [ ] Teacher sidebar, page title/breadcrumb, and dashboard card read `Grade Edit Request`.
- [ ] All three open the same unlock flow, which works exactly as before.
- [ ] Registrar/principal pages read exactly as before.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: teacher sidebar, teacher unlock page, teacher dashboard card.
- Likely areas (from code inspection): `sidebar-teacher.blade.php:26` label; `teacher/grade-unlocks/index.blade.php:6,11` breadcrumb + title; `teacher/partials/dashboard-results.blade.php:18` card title. Strings only.
- Data/records touched: none.
- Roles/permissions involved: teachers (labels); no permission change.

## 11. Approval
> Approved by user on 2026-10-08.
