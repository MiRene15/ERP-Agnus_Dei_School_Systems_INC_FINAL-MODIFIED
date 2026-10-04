# Spec: System Health Dashboard

- **Status**: Implemented (2026-10-04 — System Health with 4 cards, detail pages, strict per-row ack, 14-day graphs, dashboard strip; all acceptance checks pass)
- **Author**: Muse Spark (spec interview)
- **Created**: 2026-10-04
- **Approved by**: user on 2026-10-04 (re-approved 2026-10-04 for strict per-row ack, card order Uptime first, charts-first layout, 10-row pagination, navy buttons)
- **Parent follow-up of**: `search-rate-limit-tracking.md` (the promised IT health page)

## 1. Why We Need This

IT is blind today. When searches spike, logins spike, or lists slow down, IT only finds out after staff complain or the system feels slow — by digging through audit logs and guessing if it was normal rush or abuse. This page gives IT one calm place to see health at a glance and catch abuse or spam early, with clear alerts instead of detective work.

## 2. Who Is Affected

- **IT admin** — primary and only viewer in v1. Owns the page, checks alerts, acknowledges or clears them.
- **Cashier, registrar, librarian, teachers, nurse, other staff** — protected by it. They never open this page; their daily work is unchanged.
- **Principal / directress** — intentionally out of v1. No new view for them yet (noted for later).
- **Students and parents** — gain steadier peak days and privacy: what they typed is never shown here.

## 3. Business Flow: Today vs After

- **As-is**: IT opens audit logs, filters by user/date/words, and tries to tell rush from abuse. No warning light. No single health view.
- **To-be**:
  1. IT opens **System Health** from the left menu.
  2. IT sees 4 status cards in fixed order: Uptime, Abuse / Spam Alerts, Slow Lists, Logins + New Accounts Spikes.
  3. IT taps a card to see the detail list (which list, when, how often — never what was typed).
  4. IT taps **Acknowledge** on an alert — the red dot clears and the action is kept as a record.
- **Preserved**: Everything that must stay — who may see which list, receipts, grade locks, money records, and the full audit trail. This page only watches; it never changes money or grades.
- **Exceptions**: Transfers, refunds, corrections, and late cases are untouched. If the health page itself has no data, it says so plainly and offers Refresh — it never blocks other work.

## 4. How It Should Work

1. IT taps **System Health** in the left menu (IT menu only).
2. The page shows 4 cards in fixed order (Uptime, Abuse / Spam Alerts, Slow Lists, Logins + New Accounts Spikes) with green / yellow / red dots.
3. If a critical alert is unacknowledged, the left menu shows a small red dot on System Health.
4. IT taps a card — a designated detail page opens (`portal/admin/system-health/{type}` for `abuse`, `slow`, `uptime`, `logins`) with the full detail table (which list or area, when it happened, how many times) plus per-row and Acknowledge-all actions.
5. IT taps **Acknowledge** on an alert — the dot clears and the who/when is kept as a record.
6. IT taps **Refresh** any time to re-check.
7. Non-IT staff see no menu item and cannot open the page even by direct link — they get the normal “no permission” message.

## 5. Look & Feel (UX)

- Where it lives: left menu item **System Health**, IT admin only, next to Audit Logs. Cards link to their designated detail pages (`portal/admin/system-health/{type}`). The one primary action per alert is **Acknowledge** (per row and Acknowledge-all on detail pages).
- What IT sees first: 14-day line graphs on top (inline SVG, no new NPM dependency, Uptime first), then the 4 cards in fixed order (Uptime, Abuse / Spam Alerts, Slow Lists, Logins + New Accounts Spikes) with visual status graphics — then the tapped card's full detail table on its own page (chart first, then table at 10 rows per page) with clear Acknowledged confirmation. Refresh reloads graphs and lists together; primary buttons use the system navy style.
- What IT sees first: the 4 cards on top, then the detail list. Plain labels: “All calm”, “Unusual searches”, “Possible abuse — logins spiking”, “Slow list”.
- Key states:
  - Default: cards with green / yellow / red dots + counts.
  - Empty (all calm): “All calm — no alerts in the last 24 hours. Refresh to re-check.”
  - Warning: yellow dot + “Unusual searches on Payments — tap for details.”
  - Critical: red dot + menu badge + “Possible abuse — tap to review and Acknowledge.”
  - Error: “Health data unavailable — Refresh.” Never blank.
  - Permission-denied: normal “You don’t have access” for non-IT.
- Fewer clicks: 2 taps max from menu to alert detail. Same look in light and dark mode; cards stack on narrow screens.

## 6. Business Rules

### Must always be true

- Only IT admin may open the page; all others are denied.
- Alerts fire on patterns (many limit-hits in a short time), never on one busy cashier or one enrollment rush.
- Enrollment-week rush stays green — normal peak is not called abuse.
- Only counts are shown (how many, which list, when). What anyone typed is never stored or shown.
- Every Acknowledge / Clear keeps who did it and when as a record — stored by reusing the existing `activity_log` table (event `System Health Acknowledged`, `causer_id` = IT user, `properties` = {alert_type, route/list, counts, rows snapshot, acknowledged_at}); no new table for acks.
- Strict accuracy: an Acknowledge button (per-row or bulk) appears ONLY when there is NEW unacknowledged activity (current count for that list exceeds the count stored at the last acknowledge, or the list was never acknowledged). Otherwise the row/card shows `Acknowledged ✓ by <name> at <time>` with no button.
- Checking health never slows or blocks payments, grades, logins, or searches.

### Must never happen

- A normal cashier line or admissions rush is labeled abuse.
- Search words, names, or ID numbers appear on this page.
- An account is locked or blocked automatically from this page.
- A shared-computer spam incident locks anyone out.
- The menu badge stays after all critical alerts are acknowledged.
- Different cards invent different wording for the same state.

### Edge cases and what happens then

- Enrollment-week rush → stays green; counts rise but no red alert.
- Shared computer spam → pattern is flagged, person is not locked; list of where/when only.
- False alarm → IT taps Acknowledge / Dismiss with reason kept as a record.
- Health data missing or slow → “Health data unavailable — Refresh”, never a blank page.
- Cashier mid-payment while IT acknowledges → payment is unaffected; only the health record changes.

## 7. Out of Scope

- Auto-blocking addresses or accounts.
- Email or text push to IT on critical.
- Different alert limits per role.
- Long trend exports or custom reports (CSV export stays out; 14-day on-screen line graphs from the new history table are in scope).
- Principal summary view in v1 (noted for later).

## 8. Success Checks

- [ ] Open System Health as IT — 4 cards show with status dots plus 14-day line graphs per metric; admin dashboard shows the compact health strip with a View link.
- [ ] Acknowledge the alert — bulk and per-row buttons appear only when there is NEW activity; otherwise `Acknowledged ✓ by/when` shows with no button, and the menu red dot clears instantly with who/when kept.
- [ ] Spam search fast on a list — an alert appears with Refresh, list itself stays usable.
- [ ] Tap a card — its designated detail page opens with the full detail table and per-row plus always-available bulk Acknowledge-all.
- [ ] Open as cashier / teacher — no System Health menu and direct link is denied.
- [ ] Break or slow the data (or open with no data) — “unavailable — Refresh” appears, never blank.

## 9. Open Questions (if any)

None — all interview topics were confirmed. Assumptions confirmed at approval: IT-only in v1; pattern-based alerts only; enrollment rush stays green.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*

- Affected screens/pages:
  - Existing: `portal/admin/dashboard` gets a compact health strip (4 status dots + View System Health link; never slows the dashboard).
  - New: `portal/admin/system-health` page (cards with visual status graphics + links).
  - New: `portal/admin/system-health/{type}` designated detail pages for `abuse`, `slow`, `uptime`, `logins` (full detail table + per-row Acknowledge + Acknowledge-all + clear Acknowledged confirmation).
  - Existing to reuse: `portal/admin/audit-logs` (filters + `searchMetrics` summary), IT dashboard recent activity, `portal/partials/sidebar-admin`.
- Likely areas of the codebase (from read-only inspection):
  - Overview pattern: `Actual_Website/.../app/Http/Controllers/Portal/AdminController.php` `index()` (counts + `ActivityLog::with('causer')` recent 5) and `auditLogs()` (filters + `SearchMetricsService::summary()` passed as `searchMetrics` to `portal.admin.audit-logs`).
  - Protection + counters to reuse: `app/Providers/AppServiceProvider.php` `RateLimiter::for('search')` (60/min per-person on ajax/search GET only, counts-only `recordHit` + `log_activity('Search throttled')`, never raw search words) and `app/Services/SearchMetricsService.php` `recordHit()` / `summary()`; login spam signals in `app/Http/Requests/Auth/LoginRequest.php` (5-attempt throttle).
  - Routes to extend: `routes/web.php` `throttle:search` + `search.metrics` list/search GET routes (admin users / student-accounts / audit-logs, cashier search/payments, librarian books/student/loan search + history/visits, registrar admissions/withdrawals/report-cards, principal schedules/grades/announcements, teacher classes/class-list, nurse logs).
  - Menu badge: `resources/views/portal/partials/sidebar-admin.blade.php` next to Audit Logs link; red-dot condition = unacknowledged critical count > 0.
- Data/records touched: counts (via `SearchMetricsService`/Cache) and alert-acknowledge records (reuse `activity_log`) plus NEW `system_health_daily` history table (one row per day: abuse_hits, slow_total, login_failed, login_locked, new_accounts, uptime_ok, uptime_fail) filled by a daily snapshot artisan command (backfilled from `activity_log` for logins/acks); no search-word store; no student PII store.
- Charts: inline SVG line graphs (no new Composer/NPM dependency) reading `system_health_daily` last 14 days for abuse, slow, logins/new-accounts, and uptime.
- Roles/permissions involved: IT admin only (role 1) may view/acknowledge; all other roles denied; no permission change for their daily lists.

## 11. Approval

> Approved by user on 2026-10-04. Re-approved 2026-10-04 for strict-ack (buttons only on NEW activity), fixed card order (Uptime, Abuse, Slow, Logins), charts-first layout with combined Refresh, 10-row pagination, and navy buttons.
>
> Implemented 2026-10-04: overview + 4 detail pages + dashboard strip + history table/snapshot + SVG trends; user confirmed all acceptance checks pass.
