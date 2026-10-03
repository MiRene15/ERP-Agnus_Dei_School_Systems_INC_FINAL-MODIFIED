# Spec: Dashboard Greeting Only

- **Status**: Approved
- **Author**: Muse Spark (spec interview)
- **Created**: 2026-10-03
- **Approved by**: user on 2026-10-03

## 1. Why We Need This
Every page inside the portal greets the user with "Good morning/afternoon/evening, [Name]". On work pages like grades, payments, and schedules, this repeats on every click and pushes the real work down the screen. Keeping the greeting on dashboards only makes inner pages cleaner and faster to use.

## 2. Who Is Affected
- **All portal users** (registrar, cashier, teachers, students and parents, librarian, nurse, principal, directress, admin) — everyone sees cleaner inner pages.
- **Biggest win**: cashiers, teachers, and registrars doing repeated tasks during payments, grading, and enrollment.
- **Students and parents** — less repetition when checking grades, schedules, and ledgers.

## 3. Business Flow: Today vs After
- **As-is**: every portal page shows the same greeting bar ("Good morning, [Name]" + "[Role] Dashboard" + time and date) at the top, because it lives in the shared page header. The student dashboard even shows two welcomes stacked on each other.
- **To-be**: dashboard pages show the greeting bar exactly as today. Every other page shows no greeting bar — the page's own title becomes the top of the page and the content moves up with no empty gap.
- **Preserved**: the top-right name, role label, and avatar stay on all pages. First-visit tutorial popups still appear once. Email greetings are untouched.
- **Exceptions**: none — transfers, refunds, corrections, and late cases are unaffected. This change is look-only; no workflow or approval changes.

## 4. How It Should Work
1. User opens any dashboard — greeting bar appears as today ("Good morning, [Name]", role label, time and date).
2. User clicks to any inner page (grades, payments, schedules, books, health logs, settings, etc.) — no greeting bar appears.
3. Inner page content starts at the top with its own title; no blank space is left behind.
4. User opens the student dashboard — exactly one greeting appears (the duplicate "Welcome to your Portal" header is merged, not stacked).
5. User's first visit — the one-time tutorial popup still shows its welcome message once.

## 5. Look & Feel (UX)
- **Where it lives**: the greeting bar in the shared portal header; dashboards keep it, inner pages hide it.
- **One primary action**: unchanged per page — this spec adds nothing new, it only removes the repeated bar.
- **Key states**:
  - Default (dashboard): greeting + name + role label + time/date, as today.
  - Default (inner page): page's own title at the top, content moved up.
  - Empty: no "what to do next" change — inner pages behave as today minus the bar.
  - Error/success messages: unchanged position relative to content.
  - Permission-denied: unchanged.
- **Plain-language labels**: no new wording. The student dashboard's second header is folded into the single kept greeting.
- **What the user sees first**: on dashboards, the greeting; on inner pages, the page title and work table or form.

## 6. Business Rules
### Must always be true
- Every dashboard always shows the greeting bar.
- Every non-dashboard portal page never shows the greeting bar.
- The top-right name, role, and avatar always show.
- The one-time tutorial popup still shows once per role.

### Must never happen
- Two greetings on one screen.
- A dashboard with no greeting.
- An inner page with the greeting bar.
- An empty gap left behind where the bar was removed.

### Edge cases and what happens then
- Student dashboard duplicate ("Welcome to your Portal" + layout bar) → merged into a single greeting.
- Pages that relied on the bar as their only header → content simply moves up; no replacement header is added in v1.
- Mobile view → same rule: dashboards greet, inner pages don't.
- Public website, emails, receipts, and password messages → untouched.

## 7. Out of Scope
- Rewording the greeting or changing the morning/afternoon/evening time logic.
- Redesigning the header or adding settings to hide the clock.
- Touching the public promotional website, emails, receipts, or password messages.
- Adding new titles to inner pages that lack one.

## 8. Success Checks
- [ ] Open any dashboard — greeting with name appears.
- [ ] Open any inner page (grades, payments, schedules, books) — no greeting appears.
- [ ] Open the student dashboard — exactly one greeting appears, not two.
- [ ] Inner pages show no empty gap where the bar was.
- [ ] First-visit tutorial popup still appears once per role.

## 9. Open Questions (if any)
None — all interview topics were confirmed.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: all ~90 portal pages that share the header, plus the 10 dashboards that keep the bar (admin, registrar, cashier, teacher, student, librarian, nurse, principal, directress, main dashboard).
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - Shared header: `resources/views/portal/layouts/app.blade.php` (welcome bar block ~lines 478–488; greeting logic ~lines 296–306; greeting text ~line 481).
  - Duplicate header to merge: `resources/views/portal/student/dashboard.blade.php` (~lines 8–22).
  - Untouched: `resources/views/portal/partials/tutorial-modal.blade.php`, `resources/views/portal/partials/force-change-password-modal.blade.php`, `resources/views/emails/*`, `resources/views/PromotionalWebsite/*`.
  - Pattern: show/hide the welcome bar depending on whether the current page is a dashboard route.
- Data/records touched: none — display-only change, no data or record changes.
- Roles/permissions involved: all portal roles (display only; no permission change).

## 11. Approval
> Approved by user on 2026-10-03.
