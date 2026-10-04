# Spec: Cashier Collections & Collectibles Chart

- **Status**: Implemented
- **Created**: 2026-10-04
- **Approved by**: user on 2026-10-04
- **Parent**: `cashier-reports-hub.md` (child 3 of 3 — needs toggle + cleanup)
- **Revised**: 2026-10-04 — the pace-based **Estimate was removed** at the user's request during implementation. Scope reduced to two factual figures (Collectibles, Collections) plus a date-filtered monthly chart. Nothing projects or forecasts.
- **Re-approved by**: user on 2026-10-04 (revised scope, verified in browser)
- **Implemented**: 2026-10-04 — see §11
- **Revised again**: 2026-10-04 — production defect found in the cashier dashboard; fix specified in §12. Status was reset to **Draft**; re-approved by user on 2026-10-04 for the §12 defect fix only (`spec-rules.md` §6). The scope agreed in §1–§9 is unchanged.
- **§12 implemented**: 2026-10-04 — one-line fix at `CashierController.php:39` supplied the missing argument. **Verified in production by the user** (cashier sign-in, all six figures, date filter, Projections chart). Spec closed as `Implemented`.

## 1. Why We Need This
Cashiers see what's collected and what's owed, but had to export and do the math by hand to see it over time. Two plain figures plus a monthly trend answers "where are we?" without any interpretation.

## 2. Who Is Affected
* **Cashier** — only viewer; plans follow-ups and counters staffing.
* **Directress / principal** — untouched in v1; same numbers available on request later.

## 3. Business Flow: Today vs After
- **As-is**: outlook requires manual export math each time.
- **To-be**:
  1. Cashier opens the dashboard — the strip shows Collectibles and Collections for the selected period.
  2. Cashier taps Projections in the left menu — monthly Collections vs Collectibles lines, same chart feel as the portal's other graphs.
- **Preserved**: balances, receipts, audit trail, reminders — all untouched; the figures read only.
- **Exceptions**: a period with no receipts → "No Collections Yet" on the chart, never a fake line.

## 4. How It Should Work
1. The dashboard strip and the Projections page each show **two figures in their own cards**: **Collectibles** and **Collections**.
2. Both views carry a **From / To** date filter, pre-filled with a sensible default.
3. The Projections page also carries a **School Year** filter.
4. **Filter precedence**: custom From/To dates override the School Year. With no custom dates the Projections page uses the School Year (June–May).
5. **Defaults**: dashboard = **month to date** (1st of the month → today); Projections = **the selected School Year** (Jun 1 → May 31).
6. The two filters are **separate forms**, so choosing a School Year can never resubmit stale dates and vice versa.
7. The chart shows one line per series (Collected, Collectibles) across the months spanned by the effective period.
8. Hover shows the month's numbers; legend hides a line.

### Wording (decided 2026-10-04)
- Money **owed** = "Collectibles"
- Money **received** = "Collections" / "Collected"
- Each card carries a sub-label stating the period or the "as of" date, so the cashier always knows what the figure covers.
- Note for future revisions: "Collectibles" and "Collections" differ by one letter. If confusion is ever reported in practice, "Amount Owed" / "Amount Collected" are drop-in replacements.

## 5. Look & Feel (UX)
- The two cards sit with the existing overview cards; Projections mirrors the Reports page shell. One primary action per view.
- The two cards are one shared component, never two different-looking versions.
- Key states: default, empty ("No Collections Yet"), cashier-only menu + page.

## 6. Business Rules
### Must always be true
- **Collections** = sum of real receipts whose `payment_date` falls inside the selected period.
- **Collectibles** = receivables reconstructed **as at the period end**: for every ledger created on or before that date, `max(0, total_assessed − discount_applied − receipts booked on or before that date)`, summed. Ledgers created after the period are excluded so a later enrolment cannot appear in an earlier period.
- Both figures are **actuals**. Nothing is projected, forecast, extrapolated or promised.
- The Collectibles card names the date it is measured at.
- The selected range is clamped to **10 years**. A wider request is tightened to the 10 years ending at the period's end.
- An end date before the start date is reversed rather than treated as empty.
- The date filter is always pre-filled — never blank.

### Must never happen
- Any figure on these screens is described as an estimate, projection, forecast, promise, or expected amount.
- These screens change any balance, receipt, or reminder. They are strictly read-only.
- The chart issues one query per month. Monthly figures come from a single grouped pass, so a wide range must not scale the query count.

### Derived figures are honest reconstructions
The school stores no period-end balance snapshots. Collectibles for a past period is arithmetic over assessed fees, discounts and receipts — not an audited historical balance. A receipt back-dated into an earlier period will land in that earlier period. For the current period the figure matches the stored `student_ledgers.balance` used by the Receivables Report; a divergence means a receipt was back-dated.

## 7. Out of Scope
- Per-student breakdowns on this page, parent reminders, directress copies, export.

## 8. Success Checks
- [ ] Dashboard shows Collectibles and Collections as two separate cards, with a pre-filled From/To filter.
- [ ] Changing From/To changes both figures.
- [ ] Applying a wide date range returns promptly — no timeout.
- [ ] Projections opens from the left menu with monthly lines and follow-mouse boxes.
- [ ] Projections page shows the same two cards above the chart, plus School Year + pre-filled From/To.
- [ ] The two filters are independent: choosing a School Year clears custom dates, and vice versa.
- [ ] A period with no receipts reads "No Collections Yet" with no fake line.
- [ ] The word "Estimate" appears nowhere.
- [ ] Non-cashiers see no menu and are denied by direct link.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected: `portal/cashier/dashboard.blade.php`, `portal/cashier/partials/dashboard-results.blade.php`, new `portal/cashier/projections.blade.php`, cashier sidebar menu item, `CashierController`, `CashierProjectionService`.
- The two cards live in one shared partial, `portal/cashier/partials/projection-summary-cards.blade.php`, which reads a single `$summary` array. It is included by both the dashboard strip and the Projections page. Do not duplicate the markup.
- `CashierProjectionService::summaryForPeriod()` returns exactly `receivables`, `collections`, `periodFrom`, `periodTo`. If a key such as `estimate` reappears, the projection has crept back in.
- Period resolution: `resolveDashboardPeriod()` (custom dates else `defaultDashboardRange()` = month-to-date); `resolveProjectionPeriod()` (custom dates else `monthRangeForSchoolYear()`).
- Collectibles is derived in `receivablesAsOf()` via a `leftJoinSub` over receipts grouped per ledger — deliberately **not** `SUM(balance)`, which is a present-day snapshot.
- Chart monthly figures come from `collectionsGroupedByMonth()` — a single pass over the range's receipts, bucketed in PHP. The collectibles line subtracts the cumulative receipts from the assessed-minus-discount total of ledgers existing by the range end, keeping the whole chart at two queries.
- `clampRange()` enforces the 10-year ceiling server-side; the date inputs also carry `min="{{ $earliestDate }}"`. The server clamp is the real guard — a crafted URL bypasses the input's `min`.
- **Driver trap**: production is PostgreSQL (Supabase) even though `phpunit.xml` declares MySQL. Do not use `DATE_FORMAT`, `IFNULL`, or backtick identifiers here. Month bucketing is done in PHP precisely to stay driver-agnostic.
- View variable naming is load-bearing: `outstanding` is the multi-month **array** the chart plots, `summary['receivables']` is the single peso **figure** the card shows. They must not share a name — the Projections view merges both into one array, and a collision silently breaks the chart line.

## 11. Implementation Note (2026-10-04)

> **Superseded in part by §12.** The original note below records the build as "verified in the browser". The dashboard half of that verification did not happen: the new dashboard version was never opened on the development machine, and it failed for every production cashier on first load. §12 is the correction.
Built and verified in the browser by the user. Read-only throughout: **no migrations, no new tables, no writes to balances, receipts or reminders.** `student_ledgers` and `payments` are read only.

Delivered: `CashierProjectionService`, `ProjectionFilterRequest`, `CashierController::projections()` + a filter-aware `index()`, the Projections page, the cashier sidebar entry, the shared two-card partial, and `tests/Feature/CashierProjectionTest.php`.

Issues found and fixed during verification, recorded so they are not reintroduced:
- **Chart timeout** — the monthly series originally ran one `SUM` per month; a wide date range meant ~480 sequential queries. Replaced with a single pass bucketed in PHP.
- **PostgreSQL** — `DATE_FORMAT` (MySQL-only) broke the chart. Bucketing moved to PHP so the code is driver-agnostic. Note `phpunit.xml` still declares MySQL while production is Postgres; the suite would not catch dialect bugs.
- **Estimate misreporting every period as "Period not started"** — Carbon 3 returns a *signed* `diffInDays()`, so `daysElapsed` went negative. Since superseded by the Estimate's removal, but the same signed-diff trap applies to any future day arithmetic.

**Test suite was never executed.** PHPUnit is not installed in this project (`vendor/phpunit` is empty, `nunomaduro/collision` absent, so `php artisan test` is undefined). The 20 tests in `CashierProjectionTest.php` are committed as unrun coverage. Installing the dev dependencies and running them against a PostgreSQL test database is the recommended next step.

**Not committed.** No commit was made; the user runs git themselves per `AGENTS.md` §1.4.

**Cleanup outstanding:** `resources/views/portal/cashier/partials/projection-summary-card.blade.php` is an unused leftover from the single-card iteration and should be deleted.

---

## 12. Production Defect Fix (2026-10-04)

*Status: **implemented and verified in production 2026-10-04** — see the `§12 implemented` line in the header. No change to the scope in §1–§9; what follows repairs a fault in how it was built.*

### 12.1 What staff experienced

A cashier signs in and is met by a "Server Error" page instead of the Cashier's Office. They cannot take payments, cannot print receipts, and cannot look up a family. The page fails on **every** sign-in, so the cashier's entire day is blocked until IT intervenes.

The system itself reported nothing. The failure was discovered because a cashier said so. Production error messages were switched off and unreachable (`config/logging.php:57`), so nobody could have seen it first.

### 12.2 Why it happened

The dashboard asked for the helper that works out which dates to measure, but left out the second piece that helper needs. When a page leaves out something a helper requires, the page cannot run at all — it stops before a single figure is calculated.

This was not a slow page, not a data problem, and not a connection problem. It was **certain to fail every time**, and it failed in a way that produced no useful clue on the page itself.

### 12.3 The fix

Supply the missing piece when calling the helper. One line, in one file.

### 12.4 Why it escaped

1. **The new dashboard was never opened on the development machine.** The last local cashier request in the project's log is timestamped 20:31; the version containing the fault landed at 20:54. The first time this code ran anywhere was production.
2. **The local server can keep running an older copy of a file from memory** after the file on disk has changed. A local pass may therefore not reflect the code on disk. Restart the local server before trusting any local result.

### 12.5 Success Checks

- [ ] A cashier signs in and sees the Cashier's Office — no error page.
- [ ] All six dashboard figures appear: Today's Collection, Receipts Issued Today, Discounts to Apply, Refunds to Release, Collectibles, Collections.
- [ ] Changing the From/To dates still updates Collectibles and Collections.
- [ ] The Projections page still opens from the left menu and draws its chart.
- [ ] No other role's landing page changes behaviour.
- [ ] A repeat sign-in does not re-break the page.

### 12.6 Technical Notes

- Faulty call: `app/Http/Controllers/Portal/CashierController.php:39` — `$this->resolveDashboardPeriod($request)` passes **one** argument.
- Faulty declaration: `CashierController.php:113-116` — `resolveDashboardPeriod(ProjectionFilterRequest $request, CashierProjectionService $projectionService)` declares **two** required parameters.
- PHP raises `ArgumentCountError` before a single statement of the method executes. This is not recoverable, not catchable at the call site, and does not depend on data, dates, or filters.
- **Fix:** pass `$projectionService` as the second argument at line 39.
- **Do not change** `resolveProjectionPeriod()` — the call at line 68 already supplies all three of its arguments (declared at line 130). It is correct as written.
- **Standing rule this defect creates:** any private helper that is handed an injected service must receive that service at *every* call site. A missing required argument is invisible to code review of the method body and fatal at runtime.
- **Local verification gap:** no local request to the cashier dashboard exists in `storage/logs/laravel.log` after commit `2450500`. Confirm with a fresh local request before pushing, not only after.
- **OPcache:** XAMPP may continue executing a previously compiled copy of a file. Restart Apache to force a re-read before trusting a local result.
- **Production visibility (cross-reference: Safe Release):** `LOG_CHANNEL=stack` with no `LOG_STACK` falls back to the `single` channel, which writes to a file inside the container; Render displays only console output, so production errors are invisible. Set `LOG_CHANNEL=stderr` on Render. Until that is done, any further production fault will again reach a user before it reaches IT.
- **Not covered by this spec:** three separate pages (Subjects, Sections, Staff Accounts) fail on every action for an unrelated reason — they call a method the project's base controller does not provide. Found during this investigation; requires its own spec and commit.