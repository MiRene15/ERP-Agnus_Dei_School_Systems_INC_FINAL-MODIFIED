# Spec: Cashier Receivables Cleanup

- **Status**: Approved
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
  1. Cashier opens Receivables — three summary cards (Total Receivable, Students with Balance, plus range-free count line), then one flat table ordered biggest balance first.
  2. Each row shows Student (name + number), AR No. (latest receipt's AR, '—' when the ledger has no payment yet), Balance, and a blue totals footer — mirroring Collections.
  3. No grade groups, no 'Unknown' header, no Section column anywhere including export.
- **Preserved**: every ledger peso stays in totals and export; audit trail; payment plans; refunds/corrections handling.
- **Exceptions**: section-less students list with '—' and full pesos counted; ledgers with no payment show AR '—'.

## 4. How It Should Work
1. Receivables loads — cards on top, flat table below, footer totals the shown rows.
2. Cashier taps Export — CSV carries Student, Number, AR No., Balance (no Grade/Section).
3. Empty → "All settled", never blank.

## 5. Look & Feel (UX)
- Same cards, table, hover, footer, and dark-mode styles as Collections. One primary action: Export.
- Key states: default (all open balances), empty ("All settled"), error ("unavailable — Refresh"), cashier-only as today.

## 6. Business Rules
### Must always be true
- Totals and export include every peso, including section-less and payment-less ledgers.
- AR shown is the ledger's latest receipt AR by payment date.
- Export matches the screen.

### Must never happen
- A peso disappears with the groups.
- 'Unknown' or Section reappears in any new copy.
- Collections view changes.

### Edge cases and what happens then
- No active section → row '—', counted.
- No payment yet → AR '—', balance still listed.
- All settled → cards show ₱0.00 / 0 with "All settled" table note.

## 7. Out of Scope
- Toggle mechanics (child 1), projection chart (child 3), directress copies, reminders, per-student projections.

## 8. Success Checks
- [ ] Receivables shows 3 cards + flat biggest-first table with Student, AR No., Balance + footer — no 'Unknown', no Section.
- [ ] A section-less student appears with '—' and pesos in totals.
- [ ] Export CSV has Student, Number, AR No., Balance columns matching the screen.

## 9. Open Questions (if any)
None — flat design, latest-AR, export parity confirmed via parent interview 2026-10-04.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected: `CashierController:453-461` (`receivablesReport` — drop `groupBy`, eager-load latest payment for AR), `:502+` (`receivablesReportExport` — swap Grade/Section columns for AR No.), `partials/receivables-results.blade.php` (rebuild flat mirroring `collections-report-results.blade.php`).
- AR source: ledger's latest payment by date (`payments` relation exists on `StudentLedger`); '—' when none.
- Data: read-only reshaping; no balance math changes.
- Roles: cashier only.

## 11. Approval
> Approved by user on 2026-10-04.
