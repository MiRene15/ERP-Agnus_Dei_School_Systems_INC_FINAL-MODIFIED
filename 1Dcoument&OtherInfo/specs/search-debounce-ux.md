# Spec: Search Debounce UX

- **Status**: Implemented
- **Author**: Muse Spark (spec interview)
- **Created**: 2026-10-03
- **Approved by**: user on 2026-10-03
- **Implemented**: 2026-10-04 — 600ms uniform pause, abort/latest-wins, kept-list + Refresh states across shared + cashier/librarian bespoke; verified 7/7 checks.
- **Parent**: `search-resilience.md` (Child 1 of 2 — builds first, usable alone)

## 1. Why We Need This

Staff search all day — cashiers find students with a line waiting, librarians find books, registrars find admissions. Today each short pause starts a new search, so one name can start 3 or 4 searches. Fast typing can briefly show an old list, and the payment and catalog searches behave slightly differently from the rest. This child makes all lists calm and consistent: one search per pause or Enter, old requests dropped, list never blanked. No server limits and no tracking in this child.

## 2. Who Is Affected

- **Cashier, registrar, librarian** — primary. Less waiting, steadier lists, no extra clicks.
- **Teachers, nurse, principal, admin staff** — same calmer behavior on their lists. No training.
- **Students and parents** — shorter waits at the window. Nothing stored about them here.
- **IT admin** — untouched in this child. Their part ships in Child 2.

## 3. Business Flow: Today vs After

- **As-is**: Type "Santos" — each short pause fires. Fast typing can flash the prior result. Payment search needs 2+ letters; other lists differ slightly. Clearing while loading can leave a partial view.
- **To-be**: Type → old list stays with a small loading hint → pause briefly *or* press Enter / Search → one search, prior dropped → Clear shows the full list again. Payment and catalog now behave like all other lists.
- **Preserved**: Enter and Search always work. Who may see which list, approvals, receipts, and grade locks are unchanged. Current school year stays pre-filled where it already is.
- **Exceptions**: Transfers, refunds, corrections, and late cases need no special handling — search stays read-only and never changes money or grades. If the connection fails, the last good list stays.

## 4. How It Should Work

1. Staff opens any list with a search box.
2. Staff types — the last good list stays with a small loading hint; the list never goes blank while typing.
3. Staff pauses briefly *or* presses Enter / Search — the list updates once with the latest text.
4. Staff keeps typing fast — only the latest text wins; older unfinished work is dropped.
5. Staff taps Clear or empties the box — the full list returns and the page returns to 1.
6. If the search fails (connection lost), the screen keeps the last good list and shows "Search failed — Refresh."
7. The screen already knows how to show "Too many searches - wait a few seconds" with Refresh if the server ever sends it — that rule itself ships in Child 2.

## 5. Look & Feel (UX)

- Where it lives: the same search row on each list. No new page. The one primary action stays Search (Enter does the same).
- What the user sees first: the search box, then the list. Typing never clears it.
- Key states in plain words:
  - Typing: old list stays + small loading hint.
  - Empty result: "No matches — check spelling or Clear." plus hint where it already exists (e.g., "Try name, number, or LRN").
  - 1 letter or spaces only: no search — full list stays (payment keeps its existing 2-letter rule; other lists keep full until meaningful text).
  - Failed: "Search failed — Refresh." List kept.
  - Too-fast layout: "Too many searches - wait a few seconds." + Refresh button (shown only if server sends it; wired in Child 2).
  - No permission: unchanged.
- Plain labels: Search, Clear, Refresh. Same look in light and dark mode; bar does not break on narrow screens.

## 6. Business Rules

### Must always be true

- A short pause searches once; Enter or Search searches right away.
- Typing keeps the old list; only the latest text wins.
- Clearing or emptying the box shows the full list and returns to page 1.
- Search never creates, changes, or deletes money, grades, books, or accounts.
- Payment and catalog searches behave the same as all other lists for pause, Enter, Clear, and failed states.

### Must never happen

- One name typed normally starts 3 or 4 searches.
- An old result overwrites a newer one.
- The list goes blank while typing, on Clear, or on failure.
- Normal search needs 1 to 2 seconds of forced waiting or extra training.
- Custom searches drift back to different timing or states.

### Edge cases and what happens then

- Double Enter or double-tap → latest wins, one update; nothing charged twice because search only reads.
- Clear while loading → unfinished work dropped, full list shown.
- 1 letter or spaces only → no search; full list stays.
- Special characters → treated as plain text; list never breaks.
- Slow connection with overlapping typing → prior dropped; last good list stays with hint.
- Changing search text → back to page 1; Clear clears search plus other filters together.
- Filter plus search combined → both apply together; changing either returns to page 1.

## 7. Out of Scope

- Server limits and the "too many" rule wiring (Child 2).
- Counts, slowness tracking, or any IT view (Child 2).
- Different pause lengths per role or per list.
- New pages, new filters, saved searches, or highlight of matched text.
- Changing payment collection, grade saving, login, or public inquiry behavior.
- Mobile redesign or renaming of lists.

## 8. Success Checks

- [ ] Type a name slowly — the list updates once, not per letter.
- [ ] Press Enter — the list searches right away.
- [ ] Type fast — no old list flashes; the latest text wins.
- [ ] Tap Clear or empty the box — the full list returns on page 1.
- [ ] Break the connection (or fail a search) — "Search failed — Refresh." appears and the list stays.
- [ ] Cashier payments and librarian catalog behave the same as other lists for pause, Enter, Clear, and failed.
- [ ] Type 1 letter or spaces only — no search runs; full list stays.

## 9. Open Questions (if any)

None — pause is 600ms uniform; enrollment-week tuning (if ever needed) is a Child 2 or follow-up decision, not guessed here.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*

- Affected screens/pages: same ~20 lists as parent. Shared-pattern lists plus two bespoke: `portal/cashier/payments.blade.php` `searchPayments()` (lines ~30, 95-119: `searchQuery` + `performSearch()` fetch to `/cashier/search`, early-return if <2 chars, no cancel today) and librarian `booksManager()` / loans / borrow / visits fetches (`portal/librarian/books.blade.php:25-30,208-277`, `loans.blade.php:171-179`, `borrow.blade.php:139`, `visits.blade.php:130`).
- Likely areas of the codebase:
  - Shared: `resources/js/app.js` `ajaxTable()` — change pause from 300ms to 600ms at call sites (`@input.debounce.300ms` → 600ms), add drop-prior (cancel) so only latest resolves, keep `lastKey`/page-reset logic, preserve `loading`/`html`/`reset()`/`handlePaginationClick`.
  - Bespoke: converge `searchPayments()` and `booksManager()`-family to same pause + Enter + cancel + Clear + failed-with-list-kept behavior; keep cashier `<2 chars` early-return.
  - Too-fast view: render server "wait" message with Refresh when present (no limit logic in this child).
- Data/records touched: none.
- Roles/permissions involved: none changed; roles 2-9 see same lists as today.

## 11. Approval

> Approved by user on 2026-10-03.
