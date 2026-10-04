# Spec: Cashier Receivables Cleanup

- **Status**: Implemented (2026-10-04 — flat AR-free table, filter row, daily breakdown, collections trim; all checks pass)
- **Created**: 2026-10-04
- **Approved by**: user on 2026-10-04
- **Parent**: `cashier-reports-hub.md` (child 2 of 3 — needs `cashier-reports-toggle.md`)

## 1. Why We Need This
Receivables looks nothing like Collections: grade-group boxes with an 'Unknown' bucket, a Section column that never answers "what proves this debt", and no AR trail. Cashiers chase dues with one hand tied. One design language and AR references fix follow-up.

## 2. Who Is Affected
* **Cashier** — only viewer; dues list reads like the collections list already known.
* **Registrar, parents, directress** — untouched; statements and oversight copies don't change.

## 3. Business Flow: Today vs After
- **As-is**: receivables groups ledgers by grade ('Unknown' for section-less students) with Student | Section | Balance rows; export carries Grade/Section columns.
- **To-be**:
  1. Cashier opens Receivables — three summary cards (Total Receivable, Students with Balance, By Payment Plan breakdown), then one flat table ordered biggest balance first.
  2. Each row shows Date (latest payment date, '—' when the ledger has no payment yet), Student (name + number), LRN, Balance — balance rightmost in red. No Cashier column, no AR column: a receivable is still-to-get money with no receipt yet.
  3. Receivables has the same From/To + Generate + Export filter row as Collections (with the same From≤To pairing). The range filters rows by latest-payment date; ledgers with no payment yet always show — owed money can't be filtered away.
  4. Below the cards, a daily breakdown mirrors Collections: one tile per day (dues count + balance owed for rows whose latest payment falls that day) plus a "No payment yet" tile (count + balance). Tiles reflect the active range.
  5. Collections table becomes Date | Student | LRN | AR No. | Cashier | Amount — amount rightmost in green. Receipt No. and Plan columns are removed (AR proves the payment; plans live on the card).
  6. No bottom totals footers on either table — the cards carry totals. No grade groups, no 'Unknown' header, no Section column anywhere including exports.
- **Preserved**: every ledger peso stays in totals and export; audit trail; payment plans; refunds/corrections handling.
- **Exceptions**: section-less students list in full with pesos counted; ledgers with no payment show '—' dates and always survive filtering.

## 4. How It Should Work
1. Receivables loads — filter row, cards, daily breakdown, flat table below, no footer.
2. Cashier picks From/To + Generate — rows filter by latest-payment date; payment-less dues always stay.
3. Cashier taps Export — CSV carries the filtered rows as Date, Student, Number, LRN, Balance (no Grade/Section/AR).
3. Collections Export — CSV carries Date, Student, Number, LRN, AR No., Cashier, Amount (no Receipt No./Plan).
4. Empty → "All settled", never blank.

## 5. Look & Feel (UX)
- Same cards, table, and hover styles as Collections (no footers — cards carry totals). One primary action: Export.
- Key states: default (all open balances), empty ("All settled"), error ("unavailable — Refresh"), cashier-only as today.

## 6. Business Rules
### Must always be true
- Totals and export include every peso, including section-less and payment-less ledgers.
- Money sits rightmost: green amounts (collections), red balances (receivables).
- Range never hides payment-less dues; breakdown tiles match the filtered rows.
- Export matches the screen.

### Must never happen
- A peso disappears with the groups.
- 'Unknown' or Section reappears in any new copy.
- A totals footer returns to either table.

### Edge cases and what happens then
- No active section → row listed in full, pesos counted (no section shown anywhere).
- No payment yet → Date and AR show '—', balance still listed.
- All settled → cards show ₱0.00 / 0 with "All settled" table note.

## 7. Out of Scope
- Toggle mechanics (child 1), projection chart (child 3), directress copies, reminders, per-student projections.

## 8. Success Checks
- [ ] Receivables shows 3 cards + flat biggest-first table with Date, Student, LRN, Balance (rightmost, red) + no footer — no 'Unknown', no Section, no Cashier, no AR.
- [ ] Collections shows Date, Student, LRN, AR No., Cashier, Amount (rightmost, green) + no footer — no Receipt No., no Plan.
- [ ] Receivables filter row mirrors Collections (From/To + Generate + Export with From≤To pairing); picking a range keeps payment-less rows and narrows dated rows and tiles.
- [ ] Daily breakdown shows per-day dues tiles plus a "No payment yet" tile matching the table.
- [ ] A payment-less row shows '—' dates with pesos in totals even under an active range.
- [ ] Both exports match their screens column for column.

## 9. Open Questions (if any)
None — flat design, latest-AR, export parity confirmed via parent interview 2026-10-04.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected: `CashierController:453-461` (`receivablesReport` — flat biggest-first with latest payment for Date; apply From/To to latest-payment date, always include payment-less), `:467+` (collections export — drop Receipt/Plan columns), `:502+` (`receivablesReportExport` — respect the same range; Date/Student/Number/LRN/Balance), `partials/receivables-results.blade.php` (filter row + flat rebuild + daily tiles), `partials/collections-report-results.blade.php` (column trim + green rightmost money + drop footer), `portal/cashier/reports.blade.php` (receivables tab gets the filter row).
- AR source: ledger's latest payment by date (`payments` relation exists on `StudentLedger`); Date shown is that payment's date, '—' when none. LRN is `legacy_lrn`, falling back to student number, then '—'.
- Data: read-only reshaping; no balance math changes.
- Roles: cashier only.

## 11. Approval
> Approved by user on 2026-10-04 (re-approved for range filter + daily breakdown). Implemented 2026-10-04: flat biggest-first table (Date/Student/LRN/Balance-red), 3 cards, daily breakdown + "No payment yet" tile, From/To filter with payment-less always included, collections trimmed to Date/Student/LRN/AR/Cashier/Amount-green, both exports re-columned, filter caps at today; user confirmed all checks pass.
