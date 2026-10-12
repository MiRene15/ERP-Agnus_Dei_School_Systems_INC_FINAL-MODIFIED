# Spec: Admissions Queue Counts Per Year

- **Status**: Implemented
- **Created**: 2026-10-12
- **Approved by**: user on 2026-10-12
- **Implemented**: 2026-10-12 — year-only counts + live card swap (verification skipped at user direction, checks 1-5 unconfirmed).

## 1. Why We Need This
The registrar picks a school year in the Admissions Queue, but the four top numbers (Pending, Awaiting Downpayment, Paid, Approved) don't match that year. They stay stale, overlap, or drop to zero when a filter is on. The registrar can't trust the queue at the busiest time of enrollment.

This fix makes those four numbers always tell the truth for the school year picked — live, with no extra reload.

## 2. Who Is Affected
- **Registrar (primary)** — sees honest totals per school year and forwards the right files to the Cashier with confidence.
- **Cashier** — gets fewer wrong or duplicate forwards; no change to how they collect.
- **Families and students** — no direct change; they benefit when their file isn't missed or double-handled.
- **Principal / IT admin** — no new upkeep; same page, same permissions.

## 3. Business Flow: Today vs After
- **As-is**: Registrar opens Admissions Queue and picks e.g. 2026-2027. The table below updates, but the four top cards keep old numbers. Picking a status like "Pending" can force other cards to zero. Pending includes paid files, so Pending + Paid double-count the same child.
- **To-be**:
  1. Registrar picks a school year → all four cards instantly show that year's totals.
  2. Registrar types a search or picks a status/grade → only the table list shrinks; the four cards stay as the whole-year truth.
  3. A file is paid at the Cashier → on next refresh it leaves Pending and appears in Paid — never in both.
- **Preserved**: Same four cards, same words, same order. Same Verify All, Forward to Cashier, Return, and full-approval steps. Same family-side messages.
- **Exceptions**: Refunded or voided receipt before approval drops the file back out of Paid (history kept), exactly as today — counts follow on refresh.

## 4. How It Should Work
1. Registrar opens Admissions Queue — cards show the current school year's totals.
2. Registrar picks a different school year — all four numbers swap live, no full page reload.
3. Registrar searches or filters by status/grade — table updates; cards don't move.
4. Empty year (no files) — cards show 0 and the table shows today's "No admissions found / check back later" message.
5. Registrar forwards paid files — counts update on refresh to reflect the move.

## 5. Look & Feel (UX)
- Where it lives: same Admissions Queue page, same row of four cards at the top. One primary action stays: Forward Selected to Cashier.
- What the registrar sees first: four honest numbers for the picked year, then the working table below.
- Key states: default (year totals), empty year (0s + "check back later"), loading (today's skeleton rows while swapping), error (today's Refresh prompt — cards keep last good numbers, never blank).
- Plain-language labels; same layout, colors, and order — only the numbers get fixed in v1.
- Peak-load note: enrollment season — year swap must feel instant, with no extra waiting per keystroke in search.

## 6. Business Rules
### Must always be true
- Cards always count the picked school year only — never another year mixed in.
- Pending = waiting with NO receipt attached.
- Paid = waiting WITH a receipt attached, ready for full approval.
- Awaiting Downpayment and Approved count exact matches.
- Every file lives in exactly one of the four cards — never counted twice, never missing.
- Cards ignore search, status, and grade picks — those narrow only the table.

### Must never happen
- Cards never freeze on the old year after the registrar picks a new one.
- Cards never drop to zero just because a status filter is on.
- A paid file is never counted as both Pending and Paid.
- Awaiting Downpayment files are never shown as "has balance" in these counts.

### Edge cases and what happens then
- Old year with zero files → all cards 0, table shows "check back later."
- Returned or Rejected files → not in the four cards in v1; they stay findable via the status filter in the table.
- File just paid while registrar is looking → moves from Pending to Paid on next refresh; no manual recount.
- Two registrars working at once → each refresh shows the latest truth; no locks in v1.

## 7. Out of Scope
- Renaming any badge, filter, or table words.
- Adding Returned/Rejected cards.
- Changing Cashier-side totals or family-side status messages.
- Changing table order (pending-first, then newest stays).
- Touching approvals, sections, receipts, or audit history.

## 8. Success Checks
- [ ] Pick 2026-2027 → each card matches a manual count for that year.
- [ ] Switch school year → all four numbers swap live with no full reload.
- [ ] Pending + Paid never double-count the same file.
- [ ] Picking a status filter (e.g. Pending) leaves the other cards intact.
- [ ] Empty year shows 0s with the "check back later" table message.

## 9. Open Questions (if any)
- None.

## 10. Technical Notes (for developers)
*Plain-language pointer only - the source of truth is the code and this appendix.*
- Affected screens/pages:
  - `resources/views/portal/registrar/admissions-index.blade.php` lines 16-31 (four cards), line 42 (`ajaxTable` init), lines 49/57/66 (year/status/grade selects)
  - `resources/views/portal/registrar/partials/admissions-results.blade.php` lines 45-65 (table badge logic — already correct: Pending+receipt → Paid-ready)
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - `app/Http/Controllers/Portal/RegistrarAdmissionController@index` lines 19-76: `$query` starts year-filtered, then search (27-40) + status (42-50, Paid = Pending + not-null receipt) + grade (52-54) are applied BEFORE counts (58-61) clone it — so a status pick poisons the other three counts (Pending AND Awaiting → always 0), and `pendingCount` (line 58) includes paid files. Fix: base counts on a year-only clone; `pendingCount` adds `whereNull(downpayment_receipt)`; return all four counts in the AJAX JSON (lines 71-74 currently return only `html`) and swap badge DOM in `ajaxTable` reload.
  - No route change; same `registrar.admissions.index` with `?ajax=1`.
  - Related history: `1Dcoument&OtherInfo/specs/enrollment-payment-gating-payment-access.md` (Paid = Pending + receipt, Option A), `enrollment-payment-gating-review-forward.md`, `cashier-forwarded-new-account-display.md`.
- Data/records touched: read-only counts on `admissions.status` + `admissions.downpayment_receipt` scoped by `admissions.school_year`; no write, no migration.
- Roles/permissions involved: registrar only; no permission change.

## 11. Approval
> Approved by user on 2026-10-12.
