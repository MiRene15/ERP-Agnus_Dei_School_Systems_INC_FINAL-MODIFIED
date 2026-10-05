# Spec: Safe Actions — Calm Search

- **Status**: Approved
- **Created**: 2026-10-05
- **Approved by**: user on 2026-10-05
- **Parent**: `safe-actions-everywhere.md` (child A of 3 — builds first, usable alone)

## 1. Why We Need This
Staff search all day — cashier finds a family with a line waiting, librarian finds a book, registrar finds an admission. Today calm searching works only where its author remembered to add it. One screen fires per keystroke while its neighbour waits for a pause, so the same name behaves differently in two places. Making the pause automatic — built into the shared behaviour once — means no future screen can get it wrong.

## 2. Who Is Affected
- **Cashier, registrar, librarian** — primary. Steadier lists with no extra clicks, busiest on enrollment and fee-due days.
- **Teachers, nurse, principal, admin, IT** — same calm on their lists, no training.
- **Students and parents** — shorter waits at the window. Nothing stored about what anyone typed.

## 3. Business Flow: Today vs After
- **As-is**: most lists wait 600ms and drop older work (shipped in `search-debounce-ux`), but the guarantee is per screen. A new screen that forgets the pattern fires per keystroke.
- **To-be**:
  1. Staff types → last good list stays + small hint → pause *or* Enter/Search → one search, latest wins.
  2. Clear/empty → full list, back to page 1.
  3. Fail → list kept + "Search failed — Refresh."
  4. Payments keeps its 2-letter rule; other lists keep full-until-meaningful-text.
- **Preserved**: Enter/Search instant, abort + sequence guard, kept-list, 429 wait rendering (shipped), who-may-see-which-list, school-year pre-fill.
- **Exceptions**: transfers, refunds, corrections, late cases need nothing special — search stays read-only and never changes money or grades.

## 4. How It Should Work
1. Staff opens any list with a search box.
2. Staff types — old list stays with hint; list never blanks.
3. Staff pauses briefly *or* presses Enter/Search — list updates once with latest text.
4. Staff types fast — older unfinished work dropped, latest wins.
5. Staff taps Clear or empties box — full list returns, page 1.
6. Search fails — last good list stays + "Search failed — Refresh."
7. Payments and catalog behave exactly like all other lists for pause/Enter/Clear/fail.

## 5. Look & Feel (UX)
- Where it lives: same search row on each list. No new page. Primary action stays Search (Enter same).
- What the user sees first: search box, then list. Typing never clears it.
- Key states: typing (old list + hint); empty ("No matches — check spelling or Clear" + existing hint e.g. "Try name, number, or LRN"); 1-letter/spaces (no search, full list stays); failed ("Search failed — Refresh", list kept); permission-denied unchanged.
- Plain labels Search, Clear, Refresh. Light + dark, no narrow-screen break.

## 6. Business Rules
### Must always be true
- A short pause searches once; Enter/Search searches at once.
- Typing keeps old list; only latest wins.
- Clear/empty shows full list, page 1.
- Search never creates, changes, or deletes money, grades, books, or accounts.
- Payments + catalog match all other lists for pause/Enter/Clear/fail.
### Must never happen
- One normally typed name starts 3-4 searches.
- An old result overwrites a newer one.
- List blanks while typing, on Clear, or on fail.
- Normal search needs training or forced 1-2s wait.
- A new screen ships without the pause because its author forgot.
### Edge cases and what happens then
- Double Enter/double-tap → latest wins, one update; nothing charged twice (reads only).
- Clear while loading → unfinished dropped, full list shown.
- 1 letter/spaces → no search; full list stays (payments keeps 2-letter rule).
- Special characters → plain text; list never breaks.
- Slow overlap → prior dropped; last good list + hint.
- Search + filter → both apply; either change returns to page 1.

## 7. Out of Scope
- Server 429 wiring + countdown and hit/slow counts (already shipped in `search-rate-limit-tracking`; A only renders them).
- Per-role pause lengths, saved searches, match highlighting, mobile redesign.
- Payment/grade/login/public-inquiry changes; new filters.
- Saved searches noted as future idea only — not minimally required, adds stored history with no reported need.

## 8. Success Checks
- [ ] Type a name slowly → updates once, not per letter.
- [ ] Press Enter → searches at once.
- [ ] Type fast → no old flash; latest wins.
- [ ] Clear/empty → full list on page 1.
- [ ] Fail a search → "Search failed — Refresh", list stays.
- [ ] Payments + catalog match other lists for pause/Enter/Clear/fail.
- [ ] 1 letter/spaces → no search; full list stays.
- [ ] A new search screen behaves correctly without anyone adding anything to it.

## 9. Open Questions
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Builds on `search-debounce-ux.md` (Implemented: 600ms uniform, abort/latest-wins, kept-list). This child makes it automatic, not per-screen.
- Shared: `resources/js/app.js` — `SEARCH_PAUSE_MS = 600`, `debounceSearch()` inside the function (not the `@input.debounce` modifier, so binding cannot get it wrong), `window.AgnusSearch`, `Alpine.data('ajaxTable')` with AbortController + `_seq` + kept `html` + `reset()`/page-reset. Loaded on every portal page via `portal/layouts/app.blade.php:28` `@vite`.
- Bespoke convergence (same behaviour, keep 2-letter rule): `portal/cashier/payments.blade.php` `searchPayments()` (`@input="performSearch()"`, `@submit.prevent="performSearch.run()"`, kept-list + Refresh already present) and librarian `booksManager()` family (`portal/librarian/books.blade.php` + loans/borrow/visits — same `@input="performSearch()"` pattern).
- Data touched: none. No migration, no new table, no counters. Never log raw `search` input.
- Roles/permissions: none changed; all roles read-only on their lists.

## 11. Approval
> Approved by user on 2026-10-05.
