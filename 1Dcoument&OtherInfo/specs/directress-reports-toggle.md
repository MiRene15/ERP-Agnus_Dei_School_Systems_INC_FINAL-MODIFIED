# Spec: Directress Reports Toggle

- **Status**: Implemented (2026-10-07 — toggle, receivables/collections parity with shared tables, fonts; 8/8 checks pass)

## 1. Why We Need This

The Directress opens Reports to sign off on school finances, but the page still uses old underline tabs while the cashier's reports got a modern pill toggle. Worse, her Receivables view is a simpler, older shape than the cashier's — fewer breakdowns, no date filtering — so the two roles can look at different numbers for the same school.

This spec swaps the tabs for the proven pill toggle, brings her Collections and Receivables fully in line with the cashier's, and normalizes fonts across all five sections.

## 2. Who Is Affected

- **Directress** — the only role whose screen changes. Same five sections, modern switch, matching numbers.
- **Cashier** — untouched, but their reports become the reference both roles share.
- **Registrar, principal, teachers, parents** — no visible change.
- **IT admin** — no new tables, no new permissions.

## 3. Business Flow: Today vs After

- **As-is**: five underline tabs swap content below; Collections and Receivables run older, simpler queries than the cashier's versions.
- **To-be**:
  1. Directress opens Reports — a five-pill toggle (Collections | Receivables | Clinic | Library | Student Statistics) sits where the tabs were, defaulting to Collections with the current month pre-filled.
  2. One tap switches sections; each keeps its own dates, filters, and results exactly as left.
  3. The page address remembers the section across reload and back-button.
  4. Collections and Receivables show exactly what the cashier's versions show for the same range.
- **Preserved**: all five sections' filters, CSV exports, read-only access, audit trail, and date-limit guards.
- **Exceptions**: exports always download the shown section and range; refunds/corrections display exactly as today.

## 4. How It Should Work

1. Reports opens on Collections with the current month pre-filled.
2. One tap on any pill flips to that section with its own filters intact.
3. Flipping back restores the previous section exactly as left, without re-picking dates.
4. Reload or back-button keeps the chosen section.
5. Export CSV downloads the shown section for its shown range.
6. Collections and Receivables figures match the cashier's reports for any identical range.

## 5. Look & Feel (UX)

- **Toggle**: one segmented five-pill row replacing the underline tabs, navy active-pill style matching the cashier's toggle, dark-mode safe, horizontally scrollable on narrow screens.
- **Filters**: each section keeps its current From/To + Generate + Clear + Export row with unchanged behavior.
- **Fonts**: one consistent table/header/body style across all five sections (header `font-medium gray-600`, regular body text with dark variants) — mono/weight inconsistencies normalized.
- **States**: skeleton on first load; dimmed old results plus stale notice while switching; per-section empty states as today ("No payments found…", "All settled").

## 6. Business Rules

### Must always be true
- Toggle state survives reload and back-button.
- Each section keeps its own dates, filters, results, and export.
- Collected and owed totals never mix.
- Directress Collections and Receivables match the cashier's for any identical range (same source queries).
- All five sections share one font style, light and dark.

### Must never happen
- Switching sections loses another section's picked dates.
- Export downloads a hidden section's data.
- Non-directress roles gain access through this change.
- Any number renders in a mismatched font after this change.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Mid-load switch | Latest tap wins, no mixed content |
| Export with no rows | Empty file with headers, as today |
| Invalid date range | Inline message, no fetch (same guard as cashier reports) |
| Figures compared with cashier's | Identical by construction; any mismatch is a bug |
| Narrow screen | Pills scroll horizontally |

## 7. Out of Scope

- New report types (payroll, budget, enrollment targets)
- Changing what any report contains — same data, new switch
- Scheduled emailed reports
- Printable/PDF report layouts
- Principal or registrar report views

## 8. Success Checks

- [ ] Reports page shows a five-pill toggle instead of underline tabs, defaulting to Collections
- [ ] Switching pills swaps content; switching back restores dates and results untouched
- [ ] Reload or back-button keeps the chosen section
- [ ] Export CSV downloads the shown section and its shown range
- [ ] Collections figures match the cashier's Collections for the same range
- [ ] Receivables figures match the cashier's Receivables for the same range
- [ ] All five sections share one consistent table/header font style, light and dark
- [ ] Mid-load switch never shows mixed content

## 9. Open Questions

None — all decisions made during interview.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- **Page:** `resources/views/portal/directress/reports.blade.php` (116 lines) — replace underline tab row (lines 11–17) with the pill toggle from `resources/views/portal/cashier/reports.blade.php` (lines 10–14), extended to five pills. Tab state currently server-seeded via `$activeTab`; switch to address-bar memory (`?view=`) like the cashier toggle's `setTab`, keeping `tab` query support for the existing `library-reports` / `cashier-reports` redirect routes.
- **Parity work (the real change):** `DirectressController@receivablesReport` (line 432) is an older, simpler shape than `CashierController@receivablesReport` (line 577) — no date filtering, no by-plan/unpaid splits, grouped by grade only. Align it to the cashier's query shape and breakdowns (reuse, don't duplicate — extract shared logic if sensible). Collections queries are already the same base (payments in range); align the daily-breakdown ordering (directress sorts newest-first, cashier oldest-first) and confirm identical totals.
- **Partials:** `resources/views/portal/directress/partials/` (collections-report-results, receivables-results, clinic, library, students) — normalize fonts to the common pattern; receivables partial will change most with the parity work.
- **Routes:** `routes/web.php` lines 355–368 — no new routes needed; existing data/export routes reused. Keep the `tab`-based redirects working.
- **Data/records touched:** none — read-only change. No migration.
- **Roles/permissions involved:** directress only (existing `role:8` group).

## 11. Approval

> Approved by user on 2026-10-07.
