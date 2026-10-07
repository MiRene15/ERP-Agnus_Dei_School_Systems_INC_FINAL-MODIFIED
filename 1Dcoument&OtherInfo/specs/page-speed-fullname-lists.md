# Spec: Page Speed Fullname Lists

- **Status**: Implemented
- **Created**: 2026-10-07
- **Approved by**: user on 2026-10-07
- **Implemented**: 2026-10-07 — first-to-last full-phrase on six areas; all 7 success checks passed.

## 1. Why We Need This
On several staff lists, typing both names together finds nothing, so staff must guess to type first or last name only. That slows a queue and risks opening the wrong book or the wrong child record. Extending the cashier proof to these lists keeps every result identical while letting the natural full phrase find the row.

## 2. Who Is Affected
- **Librarian** — loans, borrows, visits, and history lists find the right student with the full phrase.
- **Nurse** — clinic logs find the right child with the full phrase.
- **Principal** — enrollment lists find the student with the full phrase.
- **Registrar and cashier** — report cards, discount requests, and the cashier ledgers leftover find the family with the full phrase.
- **IT admin** — owns the change; no new settings.
- **Registrar admissions** — untouched; already searches the whole display name.

## 3. Business Flow: Today vs After
- **As-is**: staff types "Amber Aguilar" on these lists → no rows, even though "Amber" alone or "Aguilar" alone finds her.
- **To-be**: staff types first name, last name, or both together first-to-last → the same rows as a partial search, same order, same figures; reversed order stays empty by design.
- **Preserved**: each list's existing year scoping, filters, order, page size, pause-before-search, drop-old-requests, kept-list with stale notice, wait-with-Refresh paths, press protection on writes, approvals, receipts, grade locks, and audit records.
- **Exceptions**: transfers, refunds, corrections, and late cases are unaffected — this is matching-only.

## 4. How It Should Work
1. Staff opens any covered list and types a name (2 or more letters where the list requires it) or presses Enter/Search.
2. Page waits for the usual short pause, drops any older unfinished search, and keeps the last good list visible with a small loading hint.
3. A row matches when the typed phrase appears in first name or last name — or in first name plus space plus last name together in that order, forgiving of capitalisation. Student numbers, LRNs, book titles, serials, and emails match exactly as today.
4. New results replace the list once, in the same order and figures as before; clearing the box returns the full list.

## 5. Look & Feel (UX)
- Where it lives: the same search rows on librarian loans/borrows/visits/history, nurse logs, principal enrollments, report cards, discount requests, and cashier ledgers. The one primary action stays Search (Enter works).
- What the user sees first: the search box, then the list. Typing never blanks the list.
- Key states: default (full or filtered list, existing page size); typing (old list stays plus small loading hint, dimmed with stale notice naming which search the list came from); empty ("No matches — check spelling or Clear"); too-fast and failed keep Refresh; no permission unchanged.
- Plain-language labels unchanged; same order and figures.
- Fewer clicks: normal search needs no extra press.

## 6. Business Rules
### Must always be true
- Same rows, same order, same figures as a partial search on every covered list.
- Each list keeps its existing year scoping and filters.
- Search stays forgiving of capitalisation.
- Pause-before-search, drop-old-requests, kept-list with stale notice, wait-with-Refresh, and press protection all keep working.
- First-to-last full phrase finds the row; existing single-field matches keep working.

### Must never happen
- A figure never changes meaning to go fast.
- A deliberate repeat (second loan, second log, second payment) is never blocked.
- What anyone typed into search is never stored as a routine record.
- Approvals, receipts, grade locks, or audit records never change in this child.
- No new list blanks the screen while typing.

### Edge cases and what happens then
- No match → empty list, no new error.
- Cleared or too-short search → empty list with no stale message.
- Loan, payment, or log lands mid-search → writes go through at once; the list catches up on the next pause.
- Duplicate or hyphenated and multi-word names → same set as partial search; first-to-last phrase matches as written.
- Reversed order ("Aguilar Amber") → still empty by design in this child.

## 7. Out of Scope
- Pagination or page-size changes.
- Any cache, new index, or connection work.
- Changing any figure, approval, receipt, grade lock, or audit record.
- Storing search words; registrar admissions (already fine); reversed-order matching; printed or search-engine work.

## 8. Success Checks
- [ ] Librarian lists → full phrase finds the row, same order and figures.
- [ ] Nurse logs → full phrase finds the child.
- [ ] Principal enrollments → full phrase finds the student.
- [ ] Report cards → full phrase finds the student.
- [ ] Discount requests and cashier ledgers leftover → full phrase finds the family.
- [ ] Reversed order → still empty by design.
- [ ] Typing never blanks; stale notice plus Refresh paths intact.

## 9. Open Questions (if any)
- None.

## 10. Technical Notes (for developers)
*Plain-language pointer only - the source of truth is the code and this appendix.*
- Affected screens/pages:
  - Librarian loans, borrows, visits, history lists
  - Nurse logs list
  - Principal enrollments list
  - Report cards list, discount requests list, cashier ledgers (discounts) list
  - Shared list behavior in `resources/js/app.js` — reused untouched
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - `app/Http/Controllers/Portal/LibrarianController` — five first/last split spots (loans/borrows/visits/history) → add first-to-last together match alongside existing matches
  - `app/Http/Controllers/Portal/CashierController@discounts` (~line 688) — ledgers search by student names plus user email → add together match; keep Active-year enrollment scoping, `paginate` size, and email match
  - `app/Http/Controllers/Portal/DiscountRequestController` (~line 29) — family search → add together match
  - `app/Http/Controllers/Portal/ReportCardController` (~line 30) — student search → add together match
  - `app/Http/Controllers/Portal/NurseController@logs` (~line 44) — clinic-log student search → add together match (also use bound phrase handling consistent with the rest)
  - `app/Http/Controllers/Portal/PrincipalController` (~line 385) — enrollment search → add together match
  - Existing search and list routes only; no new routes
  - Tables read: students, users, enrollments, sections, ledgers, books, loans, clinic-logs, admissions, discount-requests; no writes, no new columns
- Data/records touched: none — matching rule only.
- Roles/permissions involved: librarian, nurse, principal, registrar, cashier (same lists as today); IT admin owner; no permission change.

## 11. Approval
> Approved by user on 2026-10-07.
