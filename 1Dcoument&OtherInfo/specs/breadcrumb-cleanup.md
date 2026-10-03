# Spec: Breadcrumb Cleanup

- **Status**: Implemented
- **Author**: Muse Spark (spec interview)
- **Created**: 2026-10-03
- **Approved by**: user on 2026-10-03
- **Implemented**: 2026-10-03 — Home removed, trails start at role Dashboard (max 3 parts); check 8 recorded as pre-existing wider-mobile limitation.

## 1. Why We Need This
Every page shows "Home / Dashboard / ..." at the top. Home and Dashboard go to almost the same place, so staff see two links where one would do. It clutters the top bar and confuses first-time users ("which one takes me back?"). Starting every trail at Dashboard and keeping trails short makes the top bar cleaner and easier to trust.

## 2. Who Is Affected
- **All portal users** (registrar, cashier, teachers, students and parents, librarian, nurse, principal, directress, admin) — everyone sees the same cleaner top bar.
- **Biggest win**: registrar, cashiers, and teachers who move between pages all day during enrollment, payments, and grading.
- **Students and parents** — less confusion about where "back" goes when checking grades, schedules, and ledgers.

## 3. Business Flow: Today vs After
- **As-is**: the top bar always starts with a "Home" link (goes to the main dashboard), then a slash, then each page's own trail — e.g. Home / Cashier Dashboard / Process Payments. Dashboard pages show Home / Registrar Dashboard. Labels vary ("Dashboard" on some pages, "Cashier Dashboard" on others).
- **To-be**: the "Home" link is gone everywhere. Inner pages start at Dashboard: Dashboard (linked) / Current page (plain text). Connected pages show Dashboard / Parent page (linked) / Current page (plain text), never more than 3 parts. Dashboard pages show a single plain "Dashboard" with no slash and no link.
- **Preserved**: each Dashboard link goes to that role's existing dashboard page (cashier goes to cashier dashboard, teacher to teacher dashboard, and so on). No permissions, approvals, or workflows change. Dark mode colors and mobile layout keep working.
- **Exceptions**: transfers, refunds, corrections, and late cases are unaffected — this is look-only.

## 4. How It Should Work
1. User opens any inner page (e.g. Process Payments) — top bar shows Dashboard / Process Payments. Dashboard is a link, Process Payments is plain text.
2. User opens a connected page (e.g. My Classes > Math 7-A, or Catalog > Edit Book) — top bar shows Dashboard / parent list (link) / current record (plain text).
3. User opens any dashboard — top bar shows a single plain Dashboard, no slash, no link.
4. User clicks Dashboard in the trail — lands on their own role's dashboard page that the system already has.
5. User opens a deep page with more than 3 levels today — trail is trimmed to Dashboard / closest parent / current page.

## 5. Look & Feel (UX)
- **Where it lives**: the small trail in the top bar, left side, next to the menu button. One primary action: click Dashboard or a parent name to go back.
- **First crumb is always short "Dashboard"** (not "Cashier Dashboard" / "Registrar Dashboard"), linking to the role dashboard.
- **Style is uniform everywhere**: muted-color links, single "/" separator, current page in bold dark text. Same look in light and dark mode.
- **Key states**:
  - Default (inner page): Dashboard (link) / Current page (bold plain text).
  - Default (dashboard): single plain Dashboard.
  - Empty: no change — pages without a trail today simply show Dashboard or single Dashboard.
  - Error/success messages: position unchanged.
  - Permission-denied: unchanged.
- **What the user sees first**: the trail, then the page title below it. No extra clicks added.

## 6. Business Rules
### Must always be true
- No page shows "Home" in the trail.
- The first crumb on inner pages is always "Dashboard" and always links to that role's existing dashboard page.
- The page you are on is always plain text, never a link.
- Dashboard pages show exactly one plain "Dashboard" — no slash, no link.
- Trails never show more than 3 parts (Dashboard + at most 2 more).

### Must never happen
- "Home" appears anywhere in the trail.
- The current page is clickable.
- A Dashboard link goes to the generic main dashboard instead of the role's dashboard.
- A trail with 4 or more parts.
- A permission or workflow changes because of this cleanup.

### Edge cases and what happens then
- Connected workflow pages (Classes > Grades > Student, Books > Edit Book, Fees > Edit Fee) → show Dashboard / parent list (linked) / current page (plain), trimmed to 3 parts.
- Pages that today only show Home (fallback) → show single plain Dashboard.
- Long names on small screens → full names still show in v1 (auto-shorten with "..." is a follow-up idea only).
- Dark mode → muted links stay readable, current page stays bold dark/lilac per theme; no invisible text.

## 7. Out of Scope
- Sidebar or menu changes.
- Adding back buttons.
- Renaming page titles themselves.
- Auto-shrinking long trails on mobile with "..." (noted for later).
- Touching the public website, emails, receipts, or password messages.

## 8. Success Checks
- [ ] Open any inner page (payments, grades, catalog, schedules) — no "Home" appears.
- [ ] Every inner page trail starts with "Dashboard".
- [ ] Clicking "Dashboard" lands on that role's existing dashboard page.
- [ ] The current page name is plain text, not a link.
- [ ] A connected page (e.g. class > grades, books > edit) shows at most Dashboard / parent / current.
- [ ] Open any dashboard — a single plain "Dashboard" appears, no slash.
- [ ] Switch to dark mode — trail links and current page stay clearly readable.
- [ ] Resize to mobile width — trail does not break the top bar layout.

## 9. Open Questions (if any)
None — all interview topics were confirmed.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: all portal pages using the shared top bar (~90 views with a breadcrumbs section), plus the layout fallback.
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - Shared top bar: `Actual_Website/ERP_Agnus_Dei_School_Systems_INC/resources/views/portal/layouts/app.blade.php` (~lines 440–444: hardcoded "Home" link + slash + `@yield('breadcrumbs')` fallback).
  - Pattern today: inner pages define `@section('breadcrumbs')` with a role-dashboard link + "/" + current (e.g. `portal/cashier/payments.blade.php` lines 3–7; `portal/librarian/books.blade.php` lines 3–7 — already short "Dashboard"; `portal/teacher/grades.blade.php` lines 3–9 — 3-level example to keep).
  - Labels to shorten: pages using "Cashier Dashboard" / "Registrar Dashboard" / "Student Dashboard" as first-crumb text (e.g. `portal/registrar/dashboard.blade.php` line 4; `portal/student/dashboard.blade.php` line 4; `portal/cashier/payments.blade.php` line 4) → uniform "Dashboard"; dashboard pages → single plain "Dashboard".
  - Dashboard targets must reuse existing role routes already referenced (e.g. `cashier.dashboard`, `librarian.dashboard`, `teacher.dashboard`) — no new pages.
- Data/records touched: none — display-only change.
- Roles/permissions involved: all portal roles (display only; no permission change).

## 11. Approval
> Approved by user on 2026-10-03.
