# Spec: Page Speed List UX

- **Status**: Approved
- **Created**: 2026-10-07
- **Approved by**: user on 2026-10-07

## 1. Why We Need This
Almost every list shares one calm pattern, but the two busiest searches run their own — so staff relearn timing, totals sometimes land before rows, and peak multiplies divergent requests. Converging the last two onto the shared pattern with rows always before totals makes every list behave the same.

## 2. Who Is Affected
- **Cashier** — payments search behaves like every other list with identical balances.
- **Librarian** — catalog search behaves like every other list with identical results.
- **IT admin** — owns one pattern instead of three.
- **Everyone else** — untouched; already-shared lists don't move.

## 3. Business Flow: Today vs After
- **As-is**: cashier payments and librarian catalog run bespoke search components with their own timing; totals can render whenever they land; grade-unlocks and discount-requests already share the standard pattern and stay as-is.
- **To-be**: both bespoke searches use the shared pause, drop-old, kept-list path with rows painting before totals and the same stale, wait, and press states everywhere.
- **Preserved**: same rows, order, figures, filters, scoping, page sizes, full-phrase matching from the earlier children, approvals, receipts, grade locks, audit records, locked-year freezing.
- **Exceptions**: transfers, refunds, corrections, and late cases are unaffected — this is behavior convergence only.

## 4. How It Should Work
1. Staff types on cashier payments or librarian catalog (2 or more letters where required) or presses Enter/Search.
2. Page waits for the shared short pause, drops any older unfinished search, and keeps the last good list visible with a small loading hint.
3. Rows paint first; totals fill from the already-cached one-shot result immediately after, never before rows.
4. While reloading, the old list dims with a stale notice naming which search produced it; clearing the box returns the full list.

## 5. Look & Feel (UX)
- Where it lives: the same two search rows. The one primary action stays Search (Enter works).
- What the user sees first: the search box, then the list. Typing never blanks the list.
- Key states: default (full or filtered list); typing (old list stays plus loading hint, dimmed with stale notice); empty ("No matches — check spelling or Clear"); too-fast and failed keep Refresh; no permission unchanged — worded and placed identically on both.
- Plain-language labels unchanged; same order and figures.
- Fewer clicks: normal search needs no extra press.

## 6. Business Rules
### Must always be true
- Same rows, same order, same figures as today on both lists.
- Shared pause, drop-old, kept-list with stale notice, wait-with-Refresh, and press protection uniform on both.
- Rows always paint before totals.
- Full-phrase matching and cached hot refs from the earlier children keep working.

### Must never happen
- A figure never changes meaning to converge.
- A stale dimmed list never accepts a row action without the press-lock answering once.
- What anyone typed into search is never stored.
- Approvals, receipts, grade locks, or audit records never change in this child.
- No new list blanks the screen while typing.

### Edge cases and what happens then
- Mid-search approval or payment lands at once and the list catches up next pause; writes never wait.
- Cleared or too-short search empties with no stale message.
- Failed or rate-waited searches keep Refresh with the current list kept.
- Reversed-order phrase stays empty by design, as in the earlier children.

## 7. Out of Scope
- Pagination or page-size changes; matching-rule changes.
- Any cache, index, or connection work beyond reusing what Children 1–2 built.
- Changing any figure, approval, receipt, grade lock, or audit record.
- Storing search words; dashboard reload behavior; printed or search-engine work.

## 8. Success Checks
- [ ] Cashier payments → pauses, keeps list while typing, stale notice on reload, rows before totals.
- [ ] Librarian catalog → same uniform behavior with identical results.
- [ ] Too-fast and failed keep Refresh with the current list kept on both.
- [ ] Double-tap a row action locks safely on both.
- [ ] Grade-unlocks and discount-requests → confirmed unchanged (already shared).

## 9. Open Questions (if any)
- None.

## 10. Technical Notes (for developers)
*Plain-language pointer only - the source of truth is the code and this appendix.*
- Affected screens/pages:
  - `resources/views/portal/cashier/payments.blade.php` (`searchPayments()` bespoke)
  - `resources/views/portal/librarian/books.blade.php` (`booksManager()` bespoke)
  - Already-shared lists (grade-unlocks, discount-requests, and the rest via `ajaxTable` + `scheduleReload()`) — verify-only, untouched
  - Shared behavior in `resources/js/app.js` — extended, not forked
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - Converge both bespoke components onto the shared pause/drop-old/kept-list/stale/429/press-lock behavior; keep their distinct result markup and row actions; enforce rows-before-totals paint order using the Child 1 one-shot result and Child 2 cached refs
  - Existing search and list routes only; no new routes
  - Tables read as today; no writes, no new columns
- Data/records touched: none — behavior convergence only.
- Roles/permissions involved: cashier, librarian (same lists as today); IT admin owner; no permission change.

## 11. Approval
> Approved by user on 2026-10-07.
