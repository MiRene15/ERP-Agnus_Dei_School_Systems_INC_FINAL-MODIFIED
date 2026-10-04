# Spec: Search Rate Limit Tracking

- **Status**: Implemented (2026-10-04 — 60/min per-person search throttle on GET lists only, live countdown + Refresh with coalesced single retry, counts-only visibility in audit screens; 6/6 checks pass. Follow-up: IT-search-health-dashboard.)
- **Author**: Muse Spark (spec interview)
- **Created**: 2026-10-03
- **Approved by**: user on 2026-10-03
- **Parent**: `search-resilience.md` (Child 2 of 2 — depends on Child 1)

## 1. Why We Need This

Calm searching alone still has no backstop. A stuck key, double tab loop, bypassed page, or spam can hammer the same lists that are busiest on enrollment and fee days. IT also cannot tell normal rush from abuse or which lists are slow. This child adds a gentle per-person server rule on search lists plus basic counts of hits and slow lists — normal staff never notice it, abusers get a friendly wait message with countdown, and IT gets answers in 2 clicks without storing what anyone typed.

## 2. Who Is Affected

- **IT admin** — primary and owner. Sets the rule and sees hits and slow lists in a familiar admin screen.
- **Cashier, registrar, librarian** — protected. Normal rush feels nothing; only true spam waits briefly with the list kept.
- **Principal and directress** — gain peak-day uptime. No workflow change.
- **Teachers, nurse, admin staff** — same gentle rule on their lists.
- **Students and parents** — gain privacy: search words with names and numbers are never stored.

## 3. Business Flow: Today vs After

- **As-is (after Child 1)**: Search is calm (one search per pause or Enter), but rapid refresh, stuck keys, or bypassed pages have no backstop. IT is blind to abuse vs rush.
- **To-be**: Normal typing and Enter never trigger the rule → too-fast use keeps the current list and shows "Too many searches - wait Xs." with a live countdown number plus Refresh → countdown hits 0 or Refresh re-runs the last search → IT sees counts of hits and slow lists in the background.
- **Preserved**: Enter and Search behavior from Child 1; who may see which list; approvals, receipts, grade locks, and money records. Taking a payment or saving grades always works even when a search says wait.
- **Exceptions**: Transfers, refunds, corrections, and late cases are unaffected. If the audit list itself is filtered fast during an incident, the same gentle wait applies and filtering works after the short wait.

## 4. How It Should Work

1. Staff uses any search list normally — nothing changes.
2. Staff searches far too fast (spam, stuck key, double-tab loop, bypassed hammering) — the screen keeps the current list and shows "Too many searches - wait Xs." where Xs counts down live, plus a Refresh button.
3. Staff waits for the countdown or taps Refresh — the last search runs again.
4. Staff takes a payment, saves grades, or does other money or academic work — always allowed, even while a search says wait.
5. IT opens a familiar admin screen and sees: how often the wait was shown, which lists were hit most, and which lists were slow (over about 2 seconds) — with no search words stored.

## 5. Look & Feel (UX)

- Where it lives: the same search row on each list. No new page. Shown only when actually limited.
- The one new state: "Too many searches - wait Xs." where Xs is a live countdown number (for example, wait 20s counts 20 → 0), plus a Refresh button. List stays behind the message.
- Other states unchanged from Child 1: typing keeps old list + hint; empty "No matches — check spelling or Clear."; failed "Search failed — Refresh."
- Plain labels: Search, Clear, Refresh. Same look in light and dark mode; bar does not break on narrow screens.
- Slow lists show no staff-facing change; slowness is visible to IT only.

## 6. Business Rules

### Must always be true

- Normal rush never triggers the wait — the rule is generous and per person, on search lists only.
- When limited, the current list stays and the message shows a live countdown plus Refresh.
- Refresh re-runs the last search once the wait ends.
- Only counts are kept (how many waits, which lists, which were slow). No search words with names or numbers are stored.
- Money collection, grade saving, logins, and public inquiries are never affected by this search rule.

### Must never happen

- A normal cashier line, admissions rush, or catalog search is blocked on a busy day.
- Search words are stored as routine records.
- The list goes blank on limited, or anyone is locked out or signed out for searching too fast.
- The countdown freezes, shows no number, or Refresh does nothing after the wait.
- Different lists invent different wait messages.

### Edge cases and what happens then

- Shared computer spam → per-person wait, not a lock; list kept + countdown + Refresh.
- Stuck key or double-tab loop → same wait; no account change.
- Bypassed page or direct hammering → same server rule still applies.
- Cashier mid-payment hitting a search wait → payment goes through; only the list waits.
- IT filtering audit lists fast during an incident → same gentle wait, then filtering works.
- Slow (over about 2 seconds) vs limited vs failed → three distinct messages; slow is IT-only, limited shows countdown, failed shows "Search failed — Refresh."
- Changing search text during a wait → back to page 1 on the next run; Clear clears search plus other filters together.

## 7. Out of Scope

- Storing search words (who typed what).
- Different limits per role in v1 (same generous rule for all).
- A brand-new IT dashboard page (reuse familiar admin screens).
- Changing rules for payments, grades, logins, or public inquiries.
- New filters, saved searches, highlight of matched text, or mobile redesign.
- Enrollment-week special mode (noted for later).

## 8. Success Checks

- [ ] Search normally — the wait never appears.
- [ ] Spam or hold a key — "Too many searches - wait Xs." appears with a live countdown that counts down, plus Refresh, and the list stays.
- [ ] Countdown hits 0 or Refresh is tapped — the last search runs again.
- [ ] Take a payment or save grades while search says wait — money and grades still go through.
- [ ] IT finds waits and slow lists in 2 clicks with no student search words stored.
- [ ] Filtering the audit list fast still works after a short wait.

## 9. Open Questions (if any)

- Resolved 2026-10-04: Same generous rule year-round including enrollment week (confirmed — no roomier week in v1).
- Resolved 2026-10-04: v1 reuses existing audit screens, no new page. Follow-up spec to draft after Child 2: IT-search-health-dashboard — role-dashboard brief + full details page/button with charts (uptime, comparisons, hits/alarms for abuse including logins/creations).

Nothing here may be guessed during build.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*

- Depends on: Child 1 (`search-debounce-ux.md`) — calm pause + Enter + drop-prior + kept-list behavior must exist first; otherwise normal use would false-trigger limits.
- Likely areas of the codebase (from read-only inspection):
  - Protection pattern to extend: `app/Providers/AppServiceProvider.php` `RateLimiter::for('inquiry'|'api')` + `routes/web.php:83` `throttle:inquiry` + `routes/auth.php` `throttle:6,1`. Add a `search` limiter (generous per-user per-minute on GET list/search routes only) and apply to the ~20 list/search GET routes (admin users / student-accounts / audit-logs, cashier search/payments, librarian books/student/loan search + history/visits, registrar admissions/sections/subjects/report-cards/withdrawals, principal schedules/grades/announcements, teacher classes/class-list, nurse logs).
  - Client: Child 1 views already render the wait state; here wire 429 handling — read `Retry-After` for the Xs countdown, keep `html`/list intact, Refresh re-calls `reload()`/`performSearch()`. Never alter POST money/grade routes.
  - Visibility: prefer log + cache counters (hit counts per list, slow >2s) surfaced in existing admin views (`AdminController@auditLogs` / `index` + `ActivityLog` on `activity_log` via `log_activity()` helper in `app/helpers.php:7`) — reuse `Log::warning` + `Cache::remember` patterns already used for settings/school-year. Never log raw `search` input.
- Data/records touched: counters only; no new PII store; no per-keystroke rows.
- Roles/permissions involved: IT admin (role 1) views; roles 2-9 throttled identically on their lists; no permission change.

## 11. Approval

> Approved by user on 2026-10-03.
