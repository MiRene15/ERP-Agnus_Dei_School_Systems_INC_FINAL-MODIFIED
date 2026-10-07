# Spec: Page Speed Reference Cache

- **Status**: Implemented
- **Created**: 2026-10-07
- **Approved by**: user on 2026-10-07
- **Implemented**: 2026-10-07 — fee-set + per-grade dropdown cache with flush-on-save; all 5 success checks passed.

## 1. Why We Need This
Every search rebuilds the same rarely-changing data — fee sets by grade, section and subject lists, and year lists — from scratch. At enrollment and fee-due peaks those identical trips multiply. Caching the hot refs once and refreshing them the moment admin saves keeps every figure identical while removing the repeat.

## 2. Who Is Affected
- **Cashier and registrar** — searches reuse hot refs with identical totals and lists.
- **IT admin** — owns the cache; no new settings to watch.
- **Principal and directress** — peak-day stability with no workflow change.
- **Everyone else** — untouched in this child.

## 3. Business Flow: Today vs After
- **As-is**: each search asks for school year, settings, fee sets by grade, dropdowns, and year lists fresh, even though they rarely change mid-day.
- **To-be**: first search builds the hot refs once and reuses them; any admin save of fees, settings, sections, or years refreshes those keys at once; the very next search is fresh.
- **Preserved**: same figures, filters, order, and page sizes; pause-before-search, drop-old-requests, kept-list with stale notice, wait-with-Refresh paths, press protection, approvals, receipts, grade locks, audit records, locked-year freezing.
- **Exceptions**: transfers, refunds, corrections, and late cases are unaffected — this is hot-ref reuse only.

## 4. How It Should Work
1. Staff searches any list as today; hot refs (school year, settings, fee sets by grade, dropdowns, year list) are built once and reused.
2. Admin creates, updates, or deletes a fee schedule, setting, section, subject, or school year.
3. Those keys are refreshed in the same save action, before the success message shows.
4. The very next staff search shows the saved change; money, balances, grades, and approvals are never served from cache.
5. If the cache is empty or unavailable, pages fall back to live queries — slower but correct, with no staff-facing error.

## 5. Look & Feel (UX)
- Where it lives: nowhere new — same search rows, Search stays the one primary action. No new buttons, badges, or "as of" stamps.
- What the user sees first: the search box, then the list — unchanged.
- Key states: default, typing with kept-list plus loading hint, empty, too-fast and failed with Refresh, no permission — all unchanged.
- Plain-language labels unchanged; same order and figures.
- Fewer clicks: none added, none removed.

## 6. Business Rules
### Must always be true
- Same figures with each list's existing scoping kept.
- Every save path for fees, settings, sections, subjects, and years refreshes its keys in the same action.
- A saved fee, setting, section, or year shows on the very next search.
- Money, balances, grades, approvals, receipts, and audit records are never cached.

### Must never happen
- A saved change is never hidden behind yesterday's cached value.
- Per-student data is never cached as a hot ref.
- What anyone typed into search is never stored.
- A locked year never accepts edits through this change.
- No new list blanks the screen while typing.

### Edge cases and what happens then
- Fee edited mid-search → save refreshes at once; the in-flight search may finish on the old set but the next pause is fresh; saves never wait.
- New school year activated → year plus fee keys refresh together.
- Section or subject added → dropdowns refresh.
- Cache empty or unavailable → live queries serve the page, slower but correct.
- Concurrent saves → last save wins with a refresh each time.

## 7. Out of Scope
- Per-student, balance, or grade caching.
- Dashboard and counter caches; short-TTL result caches.
- New indexes; connection work.
- Pagination or matching-rule changes; any figure, approval, receipt, grade lock, or audit change.
- Storing search words; printed or search-engine work.

## 8. Success Checks
- [ ] Repeat the same search twice → second feels faster, same rows and figures.
- [ ] Admin assigns or edits a fee → the very next cashier search shows the new total.
- [ ] Admin changes a setting, section, or year → the very next list shows it.
- [ ] Take a payment, then search → the new balance shows at once (never stale).
- [ ] Typing never blanks; stale notice plus Refresh paths intact.

## 9. Open Questions (if any)
- None.

## 10. Technical Notes (for developers)
*Plain-language pointer only - the source of truth is the code and this appendix.*
- Affected screens/pages: cashier payments/search, registrar admissions and fee-assignment lists, section/subject dropdowns, year dropdowns; shared list behavior in `resources/js/app.js` — reused untouched.
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - Already cached (1hr, verified): `active_school_year`, `all_school_years` (enrollments + admissions + fees, sorted desc), `setting_{key}` with forget on set, `setting_locked_school_years` — see `app/helpers.php`, `app/Models/Setting`, `DirectressController` year-change forgets
  - New hot-ref cache in this child: fee sets by year+grade behind `CashierController@assessedTotalsByGradeLevel` and `FeeAssignmentController@assignLedgers` per-grade lookups; section/subject dropdown sources; year lists now calling `distinct()->pluck` per load
  - Flush in the same save action: `DirectressController@feesStore/@feesUpdate/@feesDestroy` (plus graduation-fee paths if they feed totals), `FeeAssignmentController` assign paths, `Setting::setValue`, section/subject save paths, school-year activation; locked-year guards stay first
  - Existing search and list routes only; no new routes
  - Tables read: fee_schedules, sections, subjects, settings, enrollments, admissions; no writes, no new columns; ledgers, payments, grades, audit writes excluded from cache
- Data/records touched: none — reuse only.
- Roles/permissions involved: IT admin owner; cashier, registrar gain; no permission change.

## 11. Approval
> Approved by user on 2026-10-07.
