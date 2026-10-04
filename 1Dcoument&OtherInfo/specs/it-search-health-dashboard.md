# Spec: System Health Dashboard — Combined Trends Revision

- **Status**: Implemented (2026-10-04 — cards top, Chart.js Patterns/minis, Uptime bars, boxed hovers; all 5 checks pass)
- **Author**: Muse Spark (spec interview)
- **Created**: 2026-10-04 (original) / Revised: 2026-10-04
- **Approved by**: user on 2026-10-04
- **Parent follow-up of**: `search-rate-limit-tracking.md` (the promised IT health page)

## 1. Why We Need This
IT sees four small graphs today, one per problem type. When enrollment rush or fee-due days hit, IT can't tell at a glance if Abuse, Slow lists, and Login spikes rose together. With only one day of history the graphs also show as a single dot, not a line, so patterns are invisible.

This change adds one combined crossing picture on top so IT can oversee and monitor patterns in one look.

## 2. Who Is Affected
* **IT admin** — primary viewer. Gets the combined picture and keeps the per-type minis below.
* **Cashier, registrar, librarian, teachers, nurse** — unchanged. They never open this page.
* **Principal / directress, students, parents** — unchanged. No new view for them.

## 3. Business Flow: Today vs After
- **As-is**: IT opens System Health, sees 4 separate small line pictures. With 1 day of data each shows as a dot. To compare, IT must eyeball across four boxes.
- **To-be**:
  1. IT opens System Health, sees the 4 status cards first, then the 14-day pictures below (Abuse + Slow + Logins together) with a color key.
  2. Uptime is shown as bottom-to-top bars below the combined picture (tall bar = OK, flat = issue), titled `Uptime (14d)`.
  3. The 4 small pictures below that use the same chart style as `Patterns (14d)`, drawn as proper lines when 2+ days exist. Combined picture is titled `Patterns (14d)` with no extra words.
  4. IT can hover for the day's number and tap the color key to show / hide a line.
- **Preserved**: Only IT can see it. Only counts are shown, never what anyone typed. Every Acknowledge still keeps who did it and when. Daily snapshot still fills the history.
- **Exceptions**: Transfers, refunds, corrections, late cases are untouched. No data or 1 day of data never blocks other work.

## 4. How It Should Work
1. IT taps System Health in the left menu.
2. Top shows the 4 status cards first. Below the cards shows the `Patterns (14d)` picture for the last 14 days with dates `m/d → m/d`.
3. Below it shows the `Uptime (14d)` bars rising from the bottom, then the 4 small pictures in fixed order using the same chart style as `Patterns (14d)`.
4. IT hovers (or taps on touch) a point to see that day's number.
5. IT taps a color-key item to hide / show that line.
6. With only 1 day of history, pictures show a dot plus "run snapshot tomorrow for lines".
7. Refresh reloads combined + small pictures together.

## 5. Look & Feel (UX)
- Where it lives: main System Health page only, pictures below the status cards. Detail pages and dashboard strip stay single-type to avoid crowding.
- One primary job: spot patterns fast. The big `Patterns (14d)` picture and the 4 small pictures use the same chart style as the directress demographics page (smooth lines, day labels across the bottom, number scale on the side, boxed hover that follows the mouse). `Uptime (14d)` uses bottom-to-top bars (tall = OK, flat = issue). Color key: red = Abuse, amber = Slow, blue = Logins, green = Uptime. Titles are short: `Patterns (14d)` and `Uptime (14d)`.
- What IT sees first: status cards, then `Patterns (14d)`, then `Uptime (14d)`, then minis.
- Key states:
  - Default: crossing lines with dots at each day.
  - Empty / 1 day: dot + "Collecting trends — run the daily snapshot, then graphs appear here. Run again tomorrow for lines."
  - Error: "Health data unavailable — Refresh." Never blank.
  - Permission-denied: normal "You don't have access" for non-IT.
- Same look in light and dark mode. Pictures stack on narrow screens. Plain labels: "All calm", "Unusual searches", "Possible abuse".

## 6. Business Rules
### Must always be true
- Only IT admin may open the page.
- Only counts are shown (how many, which list, when). What anyone typed is never shown.
- A proper line needs 2+ days. With 1 day show dot + hint, never a fake flat line.
- Uptime is shown as bars from the bottom because tall/flat reads instantly for OK vs issue.
- Checking health never slows payments, grades, logins, or searches.

### Must never happen
- A normal cashier line or admissions rush is labeled abuse.
- Search words, names, or ID numbers appear on this page.
- An account is locked automatically from this page.
- The menu badge stays after all critical alerts are acknowledged.

### Edge cases and what happens then
- 1 snapshot → dot + hint "run snapshot tomorrow for lines".
- 0 snapshots → empty message + what to do next.
- Enrollment-week rush → all lines rise together but stay green; counts rise, no false red.
- Small screen → lines stack, key wraps, no sideways scroll.
- Missing history → "unavailable — Refresh", never blank.

## 7. Out of Scope
- New history table or snapshot change (reuse what exists).
- Change to detail pages or dashboard strip in v1.
- Email or text alerts to IT.
- Long exports or custom reports.
- New paid chart tools. `Patterns (14d)`, the 4 small pictures, and `Uptime (14d)` reuse the directress chart approach (free CDN script already used on the demographics page).

## 8. Success Checks
- [ ] Open System Health as IT — 4 status cards on top, then `Patterns (14d)` as a smooth 3-line chart with day labels + number scale (same feel as directress), `Uptime (14d)` bars rising from the bottom below, 4 minis below in the same chart style as lines when 2+ days exist.
- [ ] Hover a point in `Patterns (14d)` — boxed tip follows the mouse showing date + Abuse/Slow/Logins. Tap key item — that line hides / shows.
- [ ] With 1 day of data — dot + "run snapshot tomorrow for lines", not a fake line.
- [ ] Open as cashier / teacher — no menu, direct link denied.
- [ ] Refresh — combined + minis update together.

## 9. Open Questions (if any)
None — uptime-separate, keep-minis-as-lines, dot+hint, main-page-only, include simple interactivity all confirmed on 2026-10-04.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages:
  - Change: `portal/admin/system-health` main page (combined + minis).
  - Unchanged: `portal/admin/system-health/{type}` detail pages, `portal/admin/dashboard` strip.
  - Reuse: `portal/admin/partials/system-health-charts.blade.php` partial.
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - Trends source: `app/Services/SystemHealthService.php` `trends()` reading last 14 rows from `system_health_daily`.
  - View: `resources/views/portal/admin/partials/system-health-charts.blade.php` (`Patterns (14d)` + 4 minis as canvases reusing the directress demographics CDN pattern; `Uptime (14d)` as bars from the bottom, labels `m/d`).
  - Page shell: `resources/views/portal/admin/system-health.blade.php` + `partials/system-health-overview-results.blade.php` (main only, below cards; Refresh must rebuild the canvas — same ajax-reload concern as any script inside `x-html`).
  - Data fill: `app/Console/Commands/SnapshotSystemHealthDaily.php` (`system-health:snapshot`), scheduled daily 23:55 in `routes/console.php`.
- Data/records touched: counts only from `system_health_daily` (abuse_hits, slow_total, login_failed+locked, uptime_ok). No search words, no PII.
- Roles/permissions involved: IT admin only; others denied.

## 11. Approval
> Approved by user on 2026-10-04 (re-approved for Uptime bars + Chart.js minis). Implemented 2026-10-04: cards top, Chart.js Patterns (3 lines) + Chart.js minis, Uptime bars bottom-up, boxed follow-mouse hovers, below-cards layout; user confirmed all 5 acceptance checks pass.
