# Spec: Cashier Dashboard Simplification

- **Status**: Implemented
- **Created**: 2026-10-04
- **Approved by**: user on 2026-10-04
- **Revision**: 2026-10-04 — card count corrected from five to **six** (§5, §8) after Slice 1 surfaced a miscount during drafting. No behavioural change; the dashboard has always been specified as four figures plus two report links. Status was reset to **Draft** for re-approval of the corrected checks (`spec-rules.md` §6), then **re-approved by user on 2026-10-04**. Slice 1 was completed before the correction.
- **Implemented**: 2026-10-04 — see §12

## 1. Why We Need This

When the cashier signs in, the page arrives **empty** and fills itself in afterwards, because the figures are fetched in the background. On a slow connection that is a cashier standing at the counter watching a blank screen.

The page then makes the cashier choose a date range before it will show anything, and works out its figures by totalling **every fee ever assessed against every receipt ever booked**. That work is slow enough to time out, and it produced four separate hangs on the development machine on the night of 4 October.

Two of the figures don't earn their place: one repeats *Today's Collection*, which is already on the page. The other is outstanding money, which belongs in a report.

Finally, the page says **"Collectibles"** right next to **"Collections."** One letter apart, meaning opposite things. The previous spec already flagged this as a risk.

## 2. Who Is Affected

- **Cashier** — the only person affected. Opens onto a page that is finished the moment it arrives.
- **Directress and Principal** — no change. They read figures through their own reports view, which is untouched.
- **IT** — the page stops doing expensive work and can no longer time out.

## 3. Business Flow: Today vs After

- **As-is**: sign in → empty page → wait → choose a date range → read two figures, one of which duplicates another on the same page and one of which needs the reports to see elsewhere.
- **To-be**:
  1. Cashier signs in and immediately sees today's takings, today's receipt count, and the two queues waiting on them.
  2. If they want outstanding money, they click **Receivables Report** on the dashboard, or **Projections** in the menu. Both are one click.
- **Preserved**: every figure remains available and unchanged. Nothing is deleted from the reports. Approvals, receipts, balances and audit records are untouched.
- **Exceptions**:
  - **A day with no receipts** → figures read ₱0 and 0. Never blank, never an error.
  - **Outstanding money is no longer on the dashboard.** During a fee-due week the cashier must open Reports to see what is owed. This is a deliberate trade: a page that always loads beats a page that occasionally stalls.
  - **No data changes.** Nothing is written, no records are moved, nothing needs undoing.

## 4. How It Should Work

1. The dashboard shows **Today's Collection** and **Receipts Issued Today**.
2. It shows **Discounts to Apply** and **Refunds to Release** — the two queues waiting on this cashier.
3. It shows **two report link cards**: **Collections Report** and **Receivables Report**.
4. **Both cards open the same Reports page**, each landing on its own tab.
5. The dashboard has **no date picker, no Apply or Reset button, and no background reload.** The figures are part of the page as it arrives.
6. The dashboard carries **no Collectibles figure and no Collections figure**.
7. **Projections is unchanged except for wording**: it keeps both figures, both filters, and the chart.
8. On Projections, **"Collectibles" becomes "Receivables"** — on the card, on the chart line, and in the page's description.
9. Projections' Receivables card is labelled **"as of" today**, never a date that has not happened yet.

## 5. Look & Feel (UX)

- **Where it lives:** unchanged — the cashier's landing page after signing in.
- **The one primary action:** see today's position at a glance, then move on. No choices to make before the page is useful.
- **What the cashier sees first:** Today's Collection, because money taken today is the first question of the morning.
- **States:**
  - *Default* — six cards: four figures and two report links, visible immediately.
  - *Empty* — only a genuine zero-day produces a zero, which is a real number, not an empty state. No "nothing to show" screen is needed.
  - *Error* — a failure must never present as an empty page. It either shows the figures or it reports that it could not.
  - *Permission denied* — unchanged; only cashiers reach this page.
- **Wording:** "Receivables" always means money owed. "Collections" always means money received. Plain language, no jargon on the page.
- **Fewer clicks:** one click to outstanding money, from either the dashboard card or the menu.

## 6. Business Rules

### Must always be true
- The dashboard carries only figures that are **today's money** or **work waiting for this cashier**. Anything needing a chosen date range belongs in a report.
- The dashboard answers in **one request**. The figures arrive with the page.
- A figure removed from the dashboard stays **reachable from the dashboard**.
- **"Receivables" means money owed and "Collections" means money received** — one word per idea, everywhere in the cashier's screen.
- An "as of" date **never names a day that has not happened yet**.
- Both report cards behave identically: same page, different tab.

### Must never happen
- The dashboard must never carry a date picker, an Apply/Reset button, or a background reload.
- The dashboard must never be slower to show its figures than it is today.
- The word **"Collectibles" must not survive anywhere** in the cashier's interface.
- A date in the future must never be presented as "as of".
- The Projections page's filters, chart shape and two figures must not change — only the wording.
- No record may be created, changed or deleted by any of this.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| No receipts booked today | ₱0 and 0 are shown. Real numbers, not a blank page. |
| Default school year ends in the future (e.g. June–May, read in October) | The Receivables card reads "as of" today. The chart still spans the whole school year. |
| Cashier wants money owed | Click **Receivables Report** or **Projections**. One click, either way. |
| A user's browser is showing an older page | A normal refresh. Nothing in the data changes, so there is nothing to reconcile. |
| Reports page remembers a tab from earlier | The link decides the tab it opens, not the earlier choice. |

## 7. Out of Scope

- The Collections Report and Receivables Report pages themselves — their tabs, filters and exports are untouched.
- The Projections page's School Year filter, From/To filter and chart — untouched.
- Directress and Principal views — untouched.
- **The other eight role dashboards.** They keep the background-reload pattern they already use; see §10 for why the cashier dashboard diverging is deliberate and must not be "corrected" later.
- Production error visibility (the live site currently cannot show its own errors) — belongs to the **Safe Release** spec.
- Three unrelated pages that fail on every action (Subjects, Sections, Staff Accounts) — a separate cause, separate spec.

## 8. Success Checks

**Dashboard**
- [ ] Cashier signs in → figures are **already visible**. No skeleton, no wait.
- [ ] Exactly **six** items show: Today's Collection, Receipts Issued Today, Discounts to Apply, Refunds to Release, and two report link cards (Collections Report and Receivables Report).
- [ ] No From/To date picker anywhere on the dashboard.
- [ ] Collections Report card → Reports page opens on the **Collections** tab.
- [ ] Receivables Report card → Reports page opens on the **Receivables** tab.
- [ ] Discounts to Apply and Refunds to Release show the **same numbers as before**.

**Projections**
- [ ] Still opens from the menu, still shows both figures and the chart.
- [ ] Card title **and** chart line both read "Receivables"; "Collectibles" appears nowhere on the page.
- [ ] Description reads "Monthly collections vs receivables."
- [ ] On the default school year, the "as of" date is **today or earlier**.

**Regression**
- [ ] Another role (e.g. Principal) signs in normally.
- [ ] Payments, Discounts and Refunds pages still work.

## 9. Open Questions

None.

## 10. Technical Notes

*Plain-language pointer only — the source of truth is the code and this appendix.*

**Controller — `app/Http/Controllers/Portal/CashierController.php`**
- `index()` (line 24) currently takes two parameters. With no date range and no background reload it needs **neither**; drop both. Role enforcement is already done by the existing `role:3` route middleware, confirmed in the production stack trace (`CheckRole::handle(..., '3')`), so no request object is needed to guard it.
- Remove the reload branch: `$isAjax` (lines 26–27) and the JSON early-return (lines 55–59).
- Remove the `resolveDashboardPeriod()` call (line 39) and the `$summary` it feeds (line 41).
- Remove view keys `summary`, `dateFrom`, `dateTo`, `periodLabel`, `earliestDate` (lines 48–52).
- **Delete `resolveDashboardPeriod()`** (lines 113–125). `index()` was its only caller, and it is the exact method whose missing argument caused the 4 October production outage.
- `ProjectionFilterRequest` **stays** — `projections()` (line 64) still uses it.

**Service — `app/Services/CashierProjectionService.php`**
- **Delete `defaultDashboardRange()`** (lines 43–48); `resolveDashboardPeriod()` was its only caller.
- **Retain** `summaryForPeriod()` (still called by `projections()` line 77), `earliestSelectableDate()` (line 86), `receivablesAsOf()`, `clampRange()`.

**Views**
- `portal/cashier/dashboard.blade.php`: line 8 description → "Today's takings and items waiting for you." Remove the `ajaxTable` wrapper (line 15), the filter card (lines 16–30), the skeleton block (lines 31–46), and the `x-html` container (line 47).
- `portal/cashier/partials/dashboard-results.blade.php`: remove the `projection-summary-cards` include (line 57). Retarget the Collections Report card (line 25) to `route('cashier.reports')` and add a matching Receivables Report card to `route('cashier.reports', ['view' => 'receivables'])`.
- **Inline and delete `dashboard-results.blade.php`.** Once nothing fetches it in the background, a file called "results" that is simply the page body is misleading. Approved by the user on 2026-10-04.
- `portal/cashier/projections.blade.php`: line 8 → "Monthly collections vs receivables."; line 66 series label → `'Receivables'` (this single string drives both the line and the legend).
- `portal/cashier/partials/projection-summary-cards.blade.php`: line 7 title → "Receivables"; line 9 "as of" date → capped at today when the period end is later.
- **Delete `portal/cashier/partials/projection-summary-card.blade.php`** (singular). Verified: included by nothing.

**Deliberate divergence — do not "fix" this**
Every role dashboard in this project follows the same shape: its controller holds a background-reload branch that renders a `partials/dashboard-results` fragment, and the page fills itself in. That is true for Admin, Registrar, Teacher, Student, Principal, Nurse, Librarian and Directress as well as Cashier.

**The cashier dashboard is the one deliberate exception.** The background reload existed so that figures could re-render when a filter changed. With no filter there is nothing to re-fetch on, and the remaining figures are four cheap counts. Removing it makes the cashier dashboard structurally different from the other eight. That is accepted, not accidental — do not reintroduce it for the sake of consistency, and do not change the other eight dashboards on the strength of this.

**Routes** — unchanged. No new routes. The Receivables tab is already selected by the `view` query parameter, read at `portal/cashier/reports.blade.php:10`.

**Tests** — `tests/Feature/CashierProjectionTest.php` references only `summaryForPeriod()` and `clampRange()`, neither of which is removed. **No test changes required.** Note the suite still does not run in this project (PHPUnit not installed).

**Data** — read-only. No migrations, no schema change, no writes. Nothing to roll back.

**Driver note** — production is PostgreSQL (Supabase); `phpunit.xml` declares MySQL. No database-dialect-specific SQL is introduced or removed by this change.

## 11. Approval

> Approved by user on 2026-10-04. Card-count correction (§5, §8) re-approved by user on 2026-10-04.

## 12. Implementation Note (2026-10-04)

**Read-only throughout: no migrations, no schema change, no writes to balances, receipts or reminders.** Nothing to roll back.

Delivered:

- **Controller** — `index()` now takes no parameters and returns `View`; the background-reload branch, the date-range resolution, `$summary` and the five view keys it fed are gone; `resolveDashboardPeriod()` deleted.
- **Service** — `defaultDashboardRange()` deleted. `summaryForPeriod()`, `receivablesAsOf()`, `clampRange()` and `earliestSelectableDate()` all retained; Projections still depends on them.
- **Dashboard** — rewritten as a single self-contained page. Six cards, no date picker, no Apply/Reset, no skeleton, no background reload. Description reads "Today's takings and items waiting for you."
- **Report links** — both cards now open the same Reports hub: Collections to `cashier.reports`, Receivables to `cashier.reports` with `view=receivables`. A new indigo clipboard-list card carries "Receivables Report / Money still owed"; indigo was the one accent colour unused on that page, so it reads as distinct rather than a fifth variation.
- **Projections wording** — description, chart series and legend, and card title all read "Receivables". A case-insensitive search for `collectible` across all Blade views now returns nothing.
- **"As of" cap** — `projections()` supplies a separate `receivablesAsOfLabel` capped at today via `min($periodTo, Carbon::today())`. `summaryForPeriod()` was deliberately left untouched because its `periodTo` is asserted by `CashierProjectionTest.php:72`; the date inputs still show the real period end, so the filter still describes what was chosen.
- **Dead code removed** — `partials/dashboard-results.blade.php` (its only reference was the deleted reload branch) and `partials/projection-summary-card.blade.php` (singular, referenced by nothing).

**Verification.** `php -l` clean on both PHP files. The user confirmed in the browser that the pages work correctly.

**Recorded honestly:** the §8 acceptance checks were confirmed by the user as a whole ("working properly") rather than walked one by one against each box. If a specific check is later found unsatisfied, treat it as an open item against this spec rather than as a passed check.

**The cashier dashboard is now the only dashboard in the project without the background-reload pattern** — see the "Deliberate divergence" note in §10. This is accepted, not accidental.

**Not committed by the agent** — the user runs git themselves (`AGENTS.md` §1.4).

**Tests unchanged.** `tests/Feature/CashierProjectionTest.php` needed no edit: it references only `summaryForPeriod()` and `clampRange()`. The suite still does not run in this project (PHPUnit not installed), so those 20 tests remain unexecuted coverage.