# Spec: List Search Filters

- **Status**: Implemented (2026-10-11 — slices 1–5 built; acceptance walkthrough still open, may reopen on user report)
- **Created**: 2026-10-11
- **Revision**: 2026-10-11 — Term picker corrected to Term 1–3 per user (school runs 3 terms).
- **Revision**: 2026-10-11 — Per-subject class lists keep the instant local filter + live counts (no server trip); server-side search applies only to paginated lists. Re-approved 2026-10-11.
- **Revision**: 2026-10-11 — Cashier student lookup stays term-free (a lookup must never hide an owing student); existing school-year picker unchanged (fee math only). Re-approved 2026-10-11.
- **Approved by**: user on 2026-10-11 (re-approved with 3-term correction)

## 1. Why We Need This

Several lists are missing the basics. There is no term filter, no per-subject student search, two screens have no search bar at all, long text breaks layouts, and tab loads feel slow — while the calm-search behavior already shipped must stay untouched. This change brings one shared filter row, the missing bars, truncation, toggle-style tabs, cache-first lists, and an Edit-view toggle to every list that needs them.

## 2. Who Is Affected

- **All list users** — same filter row, same calm search, same staleness banner everywhere.
- **Registrar** — requests search bar; grade + term + school-year filters.
- **Principal** — teacher-assign search bar.
- **Teachers** — per-subject student search; Edit-view toggle on grade assessment.
- **Cashiers** — term + school-year filters on payment lists (totals always re-validated live at payment).
- **Librarian / Nurse / IT Admin** — same truncation and toggle-tab patterns on their lists. No workflow change.

## 3. Business Flow: Today vs After

- **As-is**: most lists have calm search (pause/Enter, kept-list, wait-with-Refresh), case-forgiving matching, and fast reads; but registrar requests and teacher-assign have no search bar, no list filters by term, per-subject class lists can't be searched, schedules have no facets, long cells overflow, tabs reload fully, and every visit waits for the server.
- **To-be**: each qualifying list carries a filter row (search + term where terms exist + school year + facets + Clear) → typing follows shipped calm search → Apply/Enter filters server-side with counts → long cells truncate with full on tap → tab rows act as toggle buttons switching locally → lists paint cached rows first ("updated <time>") then refresh and append new arrivals.
- **Preserved**: pause/Enter/Clear semantics, drop-prior/latest-wins, kept-list with stale notice, wait-with-Refresh, per-person server limits, case-forgiving matching, who-may-see-which-list, approvals/receipts/grade locks, press protection on writes.
- **Exceptions**: transfers, refunds, corrections, and late cases are unaffected — filters are read-only and never change money, grades, books, or accounts.

## 4. How It Should Work

1. Staff opens any qualifying list → filter row on top: search box, Term picker (Term 1–3, current pre-filled), School Year picker (current pre-filled), facet dropdowns where relevant (schedule: day/teacher/room/level), Clear.
2. Staff types → last good list stays with loading hint → pause or Enter → one server filter with counts; changing any filter/search returns to page 1.
3. Staff taps Clear → full list returns on page 1, filters reset to current term/year.
4. Long cells show `...` → tap or hover reveals full text; status badges and row actions are never truncated; amounts are never truncated.
5. Tab rows (Approvals, Grade/Fee, promotion views) render as toggle buttons in the same order with the same badges → tap switches locally when data is loaded, fetches once when it isn't; rapid taps resolve to the latest.
6. Lists with cache show "Showing saved list from <time> — Refresh" → paint cache → refresh → update in place + append genuinely new rows; payment/grade actions stay disabled until fresh on money/grade lists.
7. Teacher opens grade assessment → View/Edit toggle beside the header flips modes; flipping with unsaved changes asks "Discard changes?" first.

## 5. Look & Feel (UX)

- Where it lives: the same filter row atop registrar requests, principal teacher-assign, per-subject class lists, schedules, cashier payments, and sibling lists — no new pages. One primary action: Search/Apply (Enter works).
- What the user sees first: filter row, then counts, then rows. Typing never blanks the list.
- Key states: default (filtered list with counts); typing (old list + hint); empty ("No matches — check spelling or Clear filters"); failed ("Search failed — Refresh"); too-fast ("Too many searches — wait Xs" + Refresh, shipped behavior); cached ("saved list from <time>"); denied (unchanged).
- Plain labels: Search, Term, School Year, Day, Teacher, Room, Clear, Refresh, View, Edit. Current term + year pre-filled; same look in light/dark; no breakage on narrow screens.

## 6. Business Rules

### Must always be true

- Filters combine with search (AND); any change returns to page 1.
- Shipped search semantics (pause, Enter, drop-prior, kept-list, wait-with-Refresh, per-person limits) keep working on every touched list.
- Cached rows are always labeled with their time and year; genuinely new rows append, never silently replace the order.
- Money and grade writes always act on fresh server state; cache never decides a payment, receipt, or grade save.
- Truncation keeps badges and actions visible; full text reachable in one tap/hover.
- Toggle-buttons preserve each tab's permission, audit, and badge behavior.
- Per-subject class lists with fully-loaded rows keep the instant local filter (no server trip); the count line reads "N of M shown" live. Server-side search with counts applies only to paginated lists.
- Cashier student lookup stays term-free — a lookup must never hide an owing student. The existing school-year picker is unchanged (fee math only).

### Must never happen

- A filter silently scoping to the wrong year or term.
- A name or amount cut with no way to see the full value.
- Cache-first showing last year's rows as this year's, or enabling payment/grade actions on stale rows.
- One list inventing its own pause timing, wait message, or clear behavior.
- Search or filters creating, changing, or deleting money, grades, books, or accounts.

### Edge cases and what happens then

- Zero matches → guidance message, list kept (last good) behind it, never a blank flash.
- Stale cache on a money/grade list → rows visible but actions disabled until refresh completes ("Updating — actions unlock when fresh").
- Rapid toggle taps → latest wins, one fetch; double-Apply → one filter run.
- Special characters in search → plain-text matching, list never breaks.
- 1-letter or spaces-only → no server search; full filtered list stays (payment keeps its 2-letter rule).
- Edit-view toggle with unsaved changes → "Discard changes?" confirm; cancel keeps the draft in place.
- Terms are Term 1–3 school-wide (confirmed 2026-10-11); any level-specific exception is a follow-up, not guessed in build.

## 7. Out of Scope

- Saved filter presets, recent-search history, matched-text highlight (noted for later).
- Column customization and export-with-filters (exports follow current approved reports).
- Per-role or per-list pause timings (uniform 600ms stays).
- Changing any figure, approval, receipt, grade lock, or audit record.

## 8. Success Checks

- [ ] New bars (registrar requests, teacher-assign, schedule facets) search once per pause/Enter with kept-list and counts. Per-subject class lists keep the instant local filter with live "N of M shown" counts (no server trip).
- [ ] Term + SY combine with search; any change returns to page 1; Clear restores current term/year.
- [ ] Long text shows `...` with full on tap; badges and actions intact; amounts never cut.
- [ ] Toggle-buttons switch locally with the same badges and order; rapid taps resolve once.
- [ ] Cached list shows its time + Refresh, then updates in place with new rows appended.
- [ ] Cashier payment totals re-validate live at payment even when the list painted from cache.
- [ ] Edit-view toggle flips assessment modes; unsaved changes ask before discarding.

## 9. Open Questions (if any)

None — 3-term count confirmed by user on 2026-10-11.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: registrar requests + admissions lists, `principal/teacher-assignments/index.blade.php`, per-subject class lists + teacher assessment view, schedules lists, cashier payments + financial views, plus sibling lists adopting the shared row.
- Likely areas of the codebase (from read-only inspection):
  - Shared: `resources/js/app.js` (`ajaxTable` + `debounceSearch`, 600ms) — extend filter objects (term, school_year, facets) through the existing reload/pagination/reset paths; keep cancel/latest-wins/stale-notice/429-countdown wiring from `search-debounce-ux.md` + `search-rate-limit-tracking.md`.
  - New bars: converge registrar-requests and `principal.teacher-assignments` (routes `web.php:415-416`) to the shared row (search + Enter + cancel + Clear + failed-kept-list); per-subject search inside class-list views; schedule facets as additional filter keys.
  - Truncation: shared cell pattern (CSS ellipsis + title/tap popover for full text); exempt badges, actions, and amounts by class.
  - Toggle-tabs: restyle existing tab controls (approvals, grade-fee, promotion) as toggle buttons without changing their data wiring; guard rapid taps (latest wins).
  - Cache-first: short-TTL list cache painted with timestamp + Refresh, then revalidate-and-merge (new rows appended); money/grade lists gate write actions on freshness; never decide figures from cache (per `page-speed-queries.md` + `cashier-projection-chart.md` lessons).
  - Edit-view toggle: mode switch on the assessment view with dirty-check confirm; no grading-logic change.
- Data/records touched: none — read shape + display only; no new stores except short-TTL list caches (no PII beyond listed rows, no search-word stores).
- Roles/permissions involved: registrar, principal, teacher, cashier (+ librarian/nurse/admin on their lists); no permission change. Relatives: `search-debounce-ux.md`, `search-rate-limit-tracking.md`, `case-insensitive-search.md`, `page-speed-*.md`, `teacher-lists-search-attendance.md` (all Implemented — this spec adds bars/filters/patterns only).
- Peak-load note: enrollment/grading/fee-due peaks — filters narrow server work (counts-first where heavy), caches are short-TTL and labeled, limits stay per-person on reads, writes untouched.

## 11. Approval

> Approved by user on 2026-10-11 (re-approved with 3-term correction).
