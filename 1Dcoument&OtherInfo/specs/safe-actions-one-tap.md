# Spec: Safe Actions — One Tap

- **Status**: Approved
- **Created**: 2026-10-05
- **Approved by**: user on 2026-10-05
- **Parent**: `safe-actions-everywhere.md` (child B of 3 — needs A; C needs this)

## 1. Why We Need This
One hammered button can fire the same work five times. A cashier double-taps Process Payment on a slow connection, a registrar double-saves an admission, a teacher double-saves grades — or someone hammers a menu link while a page loads slowly. Each hammer builds the same page or writes the same row again, overloading the system and slowing every load. One tap must mean one action: press → busy → once → released.

## 2. Who Is Affected
- **Cashier, registrar, teacher, librarian** — primary. Counter, enrollment, grading, loans: fewer doubles, less rework.
- **Principal, directress, nurse, admin, IT** — same lock on approvals, fees, logs, accounts.
- **Students/parents** — fewer duplicate charges and requests. Nothing stored about them.

## 3. Business Flow: Today vs After
- **As-is**: every press fires. Five hammers = five requests (five page builds or five writes). No button shows it is working.
- **To-be**:
  1. Press any button/link → it goes busy instantly, one request fires.
  2. Hammer the same button → dropped. Loads: newest press wins. Saves: first press holds.
  3. New page/form starts loading → busy until it renders; unload releases naturally.
  4. Save answered → existing success/error shows; button releases on error, never dead.
  5. Export/CSV hammered → one file, rest dropped; releases on download start.
- **Preserved**: what each button does; audit rows (one press = one row); back/refresh/new-tab/right-click; Search instant + latest-wins from A.
- **Exceptions**: transfers, refunds, corrections, late cases follow the same lock; two different rows/forms are deliberate and both fire.

## 4. How It Should Work
1. Staff presses Save/Confirm/Delete/Approve/Reject/Release/Apply → button busy instantly, one write fires.
2. Staff hammers same Save → rest dropped; first holds; calm "already saved" (not red) if it already went through.
3. Staff hammers a menu/form link while loading → newest load wins; spinner until render.
4. Staff double-taps Export/CSV → one file downloads.
5. Save fails or connection lost → button returns + existing error; retry is a fresh deliberate press.
6. Covers loading a new form, loading a new page, and confirming/adding into SQL — reads and writes alike.

## 5. Look & Feel (UX)
- Same buttons, same places. Only addition: busy + inert-while-working on the pressed button only — never a full-page block, so the cashier keeps reading the list while one row saves.
- Plain words, existing spots: success/error as today; repeat = calm "already saved", never technical wording.
- Readable busy in light + dark; spinner/disabled on the button; no layout shift.
- What the user sees first: their tap registered (busy), then the answer. No re-tap needed to "make it go."

## 6. Business Rules
### Must always be true
- Every press locks its button instantly + shows busy.
- Saves = first wins; loads/searches/navigation = latest wins.
- A locked button always releases — success, error, fail, thrown fetch, download start.
- Deliberate separate presses (two rows, two forms) always fire.
- Hammering lessens load (fewer duplicate builds/writes), never adds polling.
### Must never happen
- A hammer fires a second request for the same intent.
- A save is aborted for a "newer" tap (money/grades can't be un-fired).
- A button stays dead after any outcome.
- Back/refresh/new-tab/right-click gets locked.
- A full-page veil blocks the screen while one button works.
### Edge cases and what happens then
- Same Save 5x → one write, four dropped + "already saved".
- Reports → Dashboard fast → lands Dashboard (latest load wins).
- Same link 5x slow → one load, spinner to render.
- Export 2x → one file.
- Fail mid-save → button returns + error; retry is fresh.
- Confirm modals (Void/Delete) → modal's own confirm locks button-scope only.

## 7. Out of Scope
- Single-use reference + table + middleware (Child C next).
- Search pause tuning (Child A approved).
- API routes (different auth, excluded by parent).
- New screens or rewording beyond busy + release.

## 8. Success Checks
- [ ] Double-tap any Save → one write in SQL, rest dropped + calm "already saved".
- [ ] Hammer same menu/form link 5x slow → one load, spinner to render.
- [ ] Reports → Dashboard fast → lands on Dashboard.
- [ ] Double-tap Export/CSV → one file, releases on download start.
- [ ] Save fails → button returns + error; retry works.
- [ ] Button never dead after any outcome, including lost connection.
- [ ] Two different rows/forms → both fire.
- [ ] Back/refresh/new-tab never locked.

## 9. Open Questions
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- New shared behavior in `resources/js/app.js` (loaded every portal page via `portal/layouts/app.blade.php:28` `@vite`): one global submit/press guard — lock on press, busy on the button only, release on settle including thrown fetch; loads latest-wins (abort prior + seq, reusing `ajaxTable` `_controller`/`_seq` pattern), saves first-wins (drop while locked, never abort a POST).
- Coverage: 100+ `type="submit"` across portal blades (payments, grades, attendance, borrow/return, approvals, fees, withdrawals, refunds, exports) + menu/form links + Export/CSV (release on download start) + confirm-modal buttons (button-scope). Only existing guard today is `:disabled="loading"` in `force-change-password-modal.blade.php:59` — the pattern to generalize.
- Untouched: `ajaxTable.reload()/reset()/handlePaginationClick`, `debounceSearch`/`SEARCH_PAUSE_MS`, 429 countdown, routes, tables. No migration in B (table is C).
- Back/refresh/new-tab/right-click excluded by design.
- Roles/permissions: none changed.

## 11. Approval
> Approved by user on 2026-10-05.
