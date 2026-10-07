# Spec: Page Speed Queries

- **Status**: Implemented
- **Created**: 2026-10-07
- **Approved by**: user on 2026-10-07 (re-approved after full-phrase rule added)
- **Implemented**: 2026-10-07 — 10-row lists with one-shot totals plus first-to-last full-phrase on cashier; registrar 10-row with cached years; user-verified faster with "Amber Aguilar" found.

## 1. Why We Need This
Cashiers and registrars wait on lists that do the same fee work once per student shown. A 10-student list costs about a dozen database trips instead of a few, and it hurts worst with a line at the counter and rush at enrollment. Fixing the shape keeps every figure identical while removing the repeat.

## 2. Who Is Affected
- **Cashier** — finds families faster at the counter with identical balances.
- **Registrar** — admissions lists stay steady during enrollment rush with identical results.
- **IT admin** — owns the change; no new settings to maintain.
- **Everyone else** — untouched in this child.

## 3. Business Flow: Today vs After
- **As-is**: staff searches a name → page shows up to 10–20 rows → fee details are gathered in one go on the cashier side but lists still pull full columns and longer pages, and admissions counts only what the current page shows.
- **To-be**: staff searches the same way → page shows 10 rows with only the columns the screen shows → fee details for all shown rows come from a single gathered result → identical students, order, and amounts.
- **Preserved**: approvals, receipts, grade locks, audit records, role-scoped lists, Enter/Search behavior, pause-before-search, drop-old-requests, kept-list with stale notice, wait-with-Refresh paths, press protection on writes.
- **Exceptions**: transfers, refunds, corrections, and late submissions are unaffected — this is list-shape only.

## 4. How It Should Work
1. Staff opens cashier payments or registrar admissions and types a name (2 or more letters) or presses Enter.
2. Page waits for the usual short pause, drops any older unfinished search, and keeps the last good list visible with a small loading hint.
3. Page asks for the 10 matching rows with only the columns the screen shows, in the active school year only. On cashier lists a row matches when the typed phrase appears in first name, last name, student number, or LRN — or in first name plus space plus last name together in that order (for example "Amber Aguilar", forgiving of capitalisation).
4. Page gathers the grade levels of all rows shown and asks for all fee schedules for those levels in one go.
5. Each row's total is worked out from that single gathered result; a row with no grade level or no schedule shows zero, exactly as today.
6. New results replace the list once, in the same order as before; clearing the box returns the full list.

## 5. Look & Feel (UX)
- Where it lives: the same search rows on cashier payments and registrar admissions. The one primary action stays Search (Enter works).
- What the user sees first: the search box, then the list. Typing never blanks the list.
- Key states: default (full or filtered list of 10); typing (old list stays plus small loading hint, dimmed with stale notice naming which search the list came from); empty ("No matches — check spelling or Clear"); too-fast ("Too many searches - wait a few seconds" plus Refresh); failed ("Search failed — Refresh"); no permission unchanged.
- Plain-language labels unchanged; current school year stays pre-filled where it already is; same order, same amounts.
- Fewer clicks: normal search needs no extra press.

## 6. Business Rules
### Must always be true
- Same students, same order, same amounts as before on both lists.
- Active school year only; nothing spans years differently.
- Name search stays forgiving of capitalisation. On cashier lists typing both names together in first-to-last order finds the student (reversed order is not required in this child).
- Pause-before-search, drop-old-requests, kept-list with stale notice, wait-with-Refresh, and press protection all keep working.
- 10 rows per page with only shown columns.

### Must never happen
- A figure never changes meaning to go fast.
- A deliberate second payment or second application is never blocked.
- What anyone typed into search is never stored as a routine record.
- Money, grades, approvals, receipts, or audit records never change in this child.
- No new list blanks the screen while typing.

### Edge cases and what happens then
- Class with no grade level → total shows zero, no new error.
- No fee schedule for that year and grade → zero, no new error.
- Payment or enrollment lands mid-search → writes go through at once; the list catches up on the next pause.
- Duplicate names, mixed case, special characters → same matches as today. Typing both names together in first-to-last order (for example "Amber Aguilar") returns that student on cashier lists.
- Cleared or too-short search → empty list with no totals call and no message about staleness.

## 7. Out of Scope
- Reference cache and flush-on-Save (Child 2).
- List-UX convergence, deferred totals ordering, skeleton changes (Child 3).
- Dashboard and counter caches, short-TTL result caches, new indexes, connection work.
- Changing any figure, approval, receipt, grade lock, or audit record.
- Storing search words; printed flyers, posts, or search-engine work.

## 8. Success Checks
- [ ] Cashier searches a name → same students, same order, same amounts as before.
- [ ] Cashier searches both names together in first-to-last order (for example "Amber Aguilar") → that student is found.
- [ ] Registrar admissions search → same results as before.
- [ ] Lists show 10 rows with only the columns the screen already showed.
- [ ] Class with no grade level still shows zero with no new error.
- [ ] Typing never blanks the list; first load shows skeleton, reload dims plus stale notice; too-fast and failed keep Refresh.

## 9. Open Questions (if any)
- None.

## 10. Technical Notes (for developers)
*Plain-language pointer only - the source of truth is the code and this appendix.*
- Affected screens/pages:
  - `resources/views/portal/cashier/payments.blade.php` (search + payments list)
  - `resources/views/portal/registrar/admissions-index.blade.php` + `partials/admissions-results`
  - Shared list behavior in `resources/js/app.js` (`ajaxTable`, pause, abort-prior plus sequence guard) — reused untouched
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - `app/Http/Controllers/Portal/CashierController@payments/@searchStudents` + `assessedTotalsByGradeLevel` (already one-shot; standardize server path `limit(20)` to 10, keep `with(['user','enrollments.section','ledger'])` trimmed to shown columns)
  - `app/Http/Controllers/Portal/RegistrarAdmissionController@index` (keep `with('student.user')`, school-year + status + grade filters, `orderByRaw` pending-first + `latest`, `paginate(20)` → 10; reuse cached year list instead of per-load `distinct()->pluck`)
  - Existing search and list routes only; no new routes
  - Tables read: students, enrollments, sections, fee_schedules, ledgers, admissions, users; no writes, no new columns
- Data/records touched: none — read shape only.
- Roles/permissions involved: cashier, registrar (same lists as today); IT admin owner; no permission change.

## 11. Approval
> Approved by user on 2026-10-07. Re-approved 2026-10-07 after full-phrase rule added (first-to-last only).
