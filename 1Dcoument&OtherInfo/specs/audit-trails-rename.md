# Spec: Audit Trails Rename

- **Status**: Implemented
- **Created**: 2026-10-09
- **Approved by**: user on 2026-10-09 (re-approved gap fix: tutorial-modal wording)
- **Implemented**: 2026-10-09 — 5 label swaps (sidebar, heading, breadcrumb, Settings link, tutorial line); 6/6 checks pass.

## 1. Why We Need This
The admin activity screen is called "Audit Logs" in some places and "audit trail" in reports and older specs. One clear name — **Audit Trails** — removes the mixed wording so staff and IT always mean the same list.

## 2. Who Is Affected
- **IT Admin** — sees `Audit Trails` in the sidebar, page heading, breadcrumb, the small link at the bottom of Settings, and the admin welcome tutorial line. Nothing about how they search or filter changes.
- **Principal / Directress, Cashier, Registrar** — no screen change; their past actions still appear in the same list exactly as before.
- **Teachers, Librarian, Nurse** — not affected.

## 3. Business Flow: Today vs After
- **As-is**: The admin sidebar, page heading, and breadcrumb say "Audit Logs". A small link at the bottom of the Settings page says "View audit logs". The admin welcome tutorial says "review audit logs".
- **To-be**: Those five spots say "Audit Trails" / "View audit trails" / "review audit trails". Clicking, searching, and filtering work exactly as today.
- **Preserved**: Who can open the page, all filters and results, saved history, and bookmarks/old links keep working.
- **Exceptions**: Past records are not rewritten — old entries keep whatever words they already contain. Only the screen labels change.

## 4. How It Should Work
1. IT Admin opens the sidebar and sees `Audit Trails`.
2. IT Admin clicks it and the page heading and top path both read `Audit Trails`.
3. Searching and filtering the list works exactly as before.
4. The small link at the bottom of the Settings page reads `View audit trails` and opens the same page.
5. The admin welcome tutorial reads `review audit trails`.

## 5. Look & Feel (UX)
- Where it lives: admin sidebar, the activity page heading + top path, the Settings-page link, and the admin welcome tutorial line. The one primary action stays the same: search and filter the list.
- Key states: default (list with filters), empty ("no activity found" with a Clear prompt), error (with Refresh), success unchanged — same layout, only the title word changes.
- Plain-language labels: `Audit Trails` everywhere; the helper line "Track all user activity across the system." stays as-is.
- What the user sees first: the same filters and list as today, just under the new title.

## 6. Business Rules
### Must always be true
- The sidebar, page heading, breadcrumb, Settings link, and admin welcome tutorial line all say "Audit Trails" / "audit trails" with the same spelling and capital letters.
- Old links and bookmarks still open the page.
- All past activity records remain visible and unchanged.
- Only IT Admin sees this page, as today.

### Must never happen
- The page address, who can open it, or any filter or result is never changed by this rename.
- No activity record is ever edited, deleted, or reworded by this rename.
- The old name never still appears on the sidebar, heading, breadcrumb, Settings link, or admin welcome tutorial line.

### Edge cases and what happens then
- Saved bookmark to the old address → still opens, shows the new title.
- Past activity text containing the old words → left alone, not rewritten.
- Fast filtering during an incident → same gentle wait as today, then the list filters.

## 7. Out of Scope
- Renaming page addresses, permissions, file names, or stored records.
- Changing filters, search behavior, exports, or any other screen.
- Updating old spec documents or help files that mention the previous wording.

## 8. Success Checks
- [ ] Sidebar shows `Audit Trails` and opens the activity page.
- [ ] Page heading and top path both read `Audit Trails`.
- [ ] Settings page link reads `View audit trails` and opens the same page.
- [ ] Admin welcome tutorial reads `review audit trails`.
- [ ] Searching and filtering the list still works as before.
- [ ] Old bookmark to the page still opens.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only - the source of truth is the code and this appendix.*
- Affected screens/pages:
  - `portal/admin/audit-logs` page (heading + breadcrumb)
  - Admin sidebar (`sidebar-admin`)
  - `portal/admin/settings` bottom link
  - Admin welcome tutorial modal line
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - `resources/views/portal/partials/sidebar-admin.blade.php:13` — label `Audit Logs` → `Audit Trails`
  - `resources/views/portal/admin/audit-logs.blade.php:4` (breadcrumb) and `:9` (heading) — same word swap
  - `resources/views/portal/admin/settings.blade.php:114` — `View audit logs →` → `View audit trails →`
  - `resources/views/portal/partials/tutorial-modal.blade.php:4` — `review audit logs` → `review audit trails` (gap found 2026-10-09 during exec)
  - Unchanged: route name `admin.audit-logs`, address `/admin/audit-logs` (`routes/web.php:132`), `AdminController::auditLogs`, view file names, `activity_log` table contents
- Data/records touched: none — display text only, no record rewrite.
- Roles/permissions involved: IT Admin only; page guard unchanged.

## 11. Approval
> Approved by user on 2026-10-09 (initial). Re-approved gap fix (tutorial-modal wording) by user on 2026-10-09.
