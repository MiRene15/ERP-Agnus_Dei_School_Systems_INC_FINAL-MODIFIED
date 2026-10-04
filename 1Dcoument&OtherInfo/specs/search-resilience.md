# Spec: Search Resilience

- **Status**: Implemented (2026-10-04 — both children shipped: Child 1 calm search + Child 2 limits/visibility; all parent checks pass via children.)
- **Author**: Muse Spark (spec interview)
- **Created**: 2026-10-03
- **Approved by**: user on 2026-10-03

## 1. Why We Need This

Searching is how cashiers find students, librarians find books, and registrars find admissions. Today every short pause while typing starts a new search. That makes lists feel laggy, shows old results briefly, and creates extra work for the server. On busy days like enrollment and fee due dates, this risks slowdowns when staff can least afford them. There is also no gentle server backstop and no simple way for IT to see abuse or slowness. This vision fixes all three at once: calmer searching, protection on peak days, and basic visibility — without storing what anyone typed and without slowing money or grade work.

## 2. Who Is Affected

- **Cashier, registrar, librarian** — primary. They search all day with people waiting. They gain faster, steadier lists with no extra clicks.
- **IT admin** — owner. Sets the server limits and sees hits and slow searches in a place they already use.
- **Principal and directress** — gain a system that stays up on peak days. No workflow change.
- **Teachers, nurse, admin staff** — same calmer search on their lists. No training needed.
- **Students and parents** — gain shorter waits and privacy: their names and numbers typed into search are never stored as routine records.

## 3. Business Flow: Today vs After

- **As-is**: Staff types "Santos" — each short pause starts a search (3 to 4 searches for one name). Fast typing can flash an old list. Rapid refresh, a stuck key, or a bypassed page has no backstop. IT cannot tell if slowness is normal rush or abuse.
- **To-be**: Staff types → waiting a short pause searches automatically, or pressing Enter / Search searches right away → the prior unfinished search is dropped → normal rush feels instant → spam or bypass gets "Too many searches - wait a few seconds" with Refresh and the current list kept → IT sees counts of hits and slow searches in the background.
- **Preserved**: Enter and Search always work. Approvals, receipts, grade locks, and audit records for money and academic decisions stay exactly as today. Each role still sees only their own lists.
- **Exceptions**: Transfers, refunds, corrections, and late submissions are unaffected — this is search-only. If a search is limited for being too fast, money collection and grade saving still go through; only the list waits.

## 4. How It Should Work

This parent delivers value through two children, in order. Each child works on its own.

1. Staff opens any list with a search box (payments, catalog, admissions, users, visits, grades, and the rest).
2. Staff types — the list keeps showing the last good results with a small loading hint until the pause ends.
3. Staff pauses briefly *or* presses Enter / Search — the list updates once.
4. Staff clears the box or taps Clear — the full list returns.
5. If staff searches far too fast (spam, stuck key, bypass), the screen keeps the current list and shows "Too many searches - wait a few seconds" with Refresh.
6. IT opens a familiar admin screen and sees how often limits were hit and which lists were slow — with no student search words stored.

**Children (delivery order):**

| # | File | What it delivers | Depends on | Status |
|---|------|------------------|------------|--------|
| 1 | `search-debounce-ux.md` | Calmer search: short-pause auto-search + Enter instant + drop old requests + complete states | None — builds first, usable alone | Planned |
| 2 | `search-rate-limit-tracking.md` | Gentle per-person server limits on search lists + counts of hits and slow searches + friendly wait message | Child 1 | Planned |

## 5. Look & Feel (UX)

- Where it lives: the same search row on each list. No new page. The one primary action stays Search (Enter works the same).
- What the user sees first: the search box, then the list. Typing never blanks the list.
- Key states in plain words:
  - Default: full or filtered list.
  - Typing: old list stays + small loading hint.
  - Empty: "No matches — check spelling or Clear."
  - Too-fast: "Too many searches - wait a few seconds." + Refresh button.
  - Failed: "Search failed — Refresh."
  - No permission: unchanged.
- Fewer clicks: normal search needs no extra press. Defaults like current school year stay pre-filled where they already are.
- Same behavior for all roles; each role's list content and order stay role-appropriate (cashier sees money first, librarian sees books first).

## 6. Business Rules

### Must always be true

- Pressing Enter or Search searches right away.
- The current list stays on screen while typing, on too-fast, and on failure.
- Server limits are generous and per person, and apply to search lists only.
- Only counts are tracked (how many hits, which lists were slow). No search words are stored as routine records.
- Taking a payment, saving grades, and other money or academic actions always work even when a search says wait.

### Must never happen

- A normal cashier, registrar, or librarian rush is blocked on a busy day.
- Search words with student names or numbers are stored as routine tracking.
- The list goes blank because of too-fast searching.
- Normal search needs an extra step or new training.
- A shared computer spam incident locks anyone's account.

### Edge cases and what happens then

- Double Enter or double-tap Search → the latest search wins; nothing is charged or changed twice because search only reads.
- Shared computer rapid refresh or stuck key → "Too many searches - wait a few seconds" with Refresh; list kept, no lockout.
- Bypassed page or old tab → the same server limit still applies.
- Slow connection → old unfinished search is dropped; last good list stays with loading hint.
- Search inside audit screens → same gentle rules; IT can still filter.
- Cashier mid-payment hitting a search limit → payment goes through; only the list waits.

## 7. Out of Scope

- Storing every search word as a record (who typed what).
- Different limits per role in v1 (same generous rule for all).
- A brand-new IT dashboard page in v1 (reuse familiar admin screens).
- Changing limits for payments, grades, logins, or public inquiries.
- New filters, advanced search options, or mobile redesign.
- Renaming lists or changing who may see which list.

## 8. Success Checks

- [ ] Type a name slowly — the list updates once, not per letter.
- [ ] Press Enter — the list searches right away.
- [ ] Spam or hold a key — "Too many searches - wait a few seconds" appears with Refresh and the list stays.
- [ ] Take a payment or save grades still works even when search says wait.
- [ ] IT finds hits and slow lists in 2 clicks with no student search words stored.
- [ ] Child 1 works on its own; Child 2 adds protection without changing Child 1 behavior.

## 9. Open Questions (if any)

- Same limits during enrollment week, or roomier that week? Default in children: same generous rule for all weeks unless you decide otherwise at approval.
- IT view inside existing audit screens vs small health card on settings? Default in children: reuse existing screens, no new page.

Nothing here may be guessed during build — Child specs must resolve these at their approval.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*

- Affected screens/pages: all lists using the shared search pattern (~20): cashier payments + search, librarian books / loans / history / visits + book/student/loan search, registrar admissions / sections / subjects / report-cards / withdrawals, principal schedules / grades / announcements, teacher classes / class-list, nurse logs, admin users / student-accounts / audit-logs.
- Likely areas of the codebase (from read-only inspection):
  - Shared list behavior: `resources/js/app.js` `ajaxTable()` — current `@input.debounce.300ms` call sites in Blade (e.g. `portal/cashier/payments.blade.php:30`, `portal/librarian/books.blade.php:29`, plus `ajaxTable` usage in `portal/admin/audit-logs.blade.php:13`).
  - Two patterns exist: `ajaxTable(filters)` lists and bespoke `searchPayments()` / `booksManager()` in cashier/librarian — Child 1 must cover both or converge them.
  - Server protection pattern to extend: `app/Providers/AppServiceProvider.php` `RateLimiter::for('inquiry'|'api')` + `routes/web.php:83` `throttle:inquiry` + `routes/auth.php` `throttle:6,1`. Add a `search` limiter and apply to list/search GET routes only.
  - Tracking store: prefer log + cache counters surfaced in existing admin views (`AdminController@auditLogs`, `ActivityLog` model on `activity_log` table) — avoid new table / avoid per-keystroke rows. Never log raw `search` input with LRN/name.
- Data/records touched: no new PII store; counters only.
- Roles/permissions involved: IT admin (role 1) owns; roles 2-9 affected read-only on their lists; no permission change in parent.

## 11. Approval

> Approved by user on 2026-10-03.
