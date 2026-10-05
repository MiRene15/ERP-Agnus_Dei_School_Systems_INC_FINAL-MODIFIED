# Spec: Cashier Reports Hub

- **Status**: Approved
- **Created**: 2026-10-04
- **Approved by**: user on 2026-10-04 (original); re-approved by user on 2026-10-05 for supersede notes below
- **Revised**: 2026-10-05 — superseded in part by children: pace-based **Estimate removed** (`cashier-projection-chart.md` rev 2026-10-04) and **Collectibles → Receivables** wording (`cashier-dashboard-simplification.md` 2026-10-04). This parent remains the vision + build order; §§3–4 item 3, §6 Estimate rule, and §8 dashboard-strip check are superseded as noted inline. No other scope change.

## 1. Why We Need This
Cashiers live in Reports during fee season, but Collections and Receivables feel like two different tools: two tabs, two designs, and a receivables table with an 'Unknown' group and a Section column that answers nothing. Cashiers also can't see where balances are heading without exporting and computing by hand. One hub vision fixes all three: one switch, one design language, and balances visible at a glance.

## 2. Who Is Affected
* **Cashier** — primary. Faster daily collections review and dues follow-up, no retraining.
* **Registrar** — enrollment changes flow into the same balances; read-only benefit.
* **Directress / principal** — oversight copies unchanged in v1; gain consistency later.
* **Parents / students** — statements untouched; gain steadier follow-up later.
* **IT admin** — no new permissions; audit trail preserved.

## 3. Business Flow: Today vs After
- **As-is**: cashier opens Reports, picks one of two tabs. Receivables groups students by grade with an 'Unknown' bucket and a Section column; AR numbers live only in Collections. Balance outlook requires manual export math.
- **To-be**:
  1. Cashier opens Reports — one toggle switches Collections/Receivables on the same page.
  2. Receivables reads like Collections: summary cards, flat table with AR No., totals footer; no 'Unknown', no Section column.
  3. Cashier opens the dashboard — outstanding + Estimate strip visible immediately; full projection on its own left-menu page. **\[Superseded 2026-10-05: Estimate removed per `cashier-projection-chart.md` rev; dashboard now per `cashier-dashboard-simplification.md` — today's takings + queues, no Estimate strip.\]**
- **Preserved**: receipts/AR numbering, audit trail, payment plans, refunds/corrections handling, existing monthly payment reminders (untouched, no spec — see follow-ups).
- **Exceptions**: transfers, refunds, and corrections stay in totals; students with no active section appear with '—' and their pesos still count.

## 4. How It Should Work
1. Cashier taps Reports — toggle defaults to Collections with From/To, Generate, Export.
2. Cashier flips the toggle — Receivables appears with its own filters and Export, same card/table/footer design.
3. Cashier taps the dashboard — strip shows outstanding total plus Estimate for the month at current pace. **\[Superseded 2026-10-05: no Estimate strip; see `cashier-dashboard-simplification.md` §4.\]**
4. Cashier taps Projections in the left menu — monthly collected-vs-outstanding lines with day labels. **\[Wording update 2026-10-05: outstanding = Receivables; see `cashier-dashboard-simplification.md` §4.8–4.9.\]**

## 5. Look & Feel (UX)
- Where it lives: Reports page (toggle), cashier dashboard (strip), new left-menu Projections page. One primary action per view.
- Key states: default (current month pre-filled), empty ("All settled" / "No Collections Yet"), error ("unavailable — Refresh", never blank), permission-denied (cashier-only, as today).
- Plain labels: Collections, Receivables, Outstanding, Estimate, AR No. **\[Superseded 2026-10-05: Estimate removed; labels are Collections / Receivables only.\]**
 Blocked/empty states say what to do next.

## 6. Business Rules
### Must always be true
- Collected and owed never mix in one total.
- Every peso ties to a receipt/AR; totals always include every ledger peso, including section-less students.
- Projection is labeled Estimate and derives from real collection pace only. **\[Superseded 2026-10-05: Estimate removed — both figures are actuals only; see `cashier-projection-chart.md` §6.\]**
- Export matches what's on screen for the same range.

### Must never happen
- Pesos vanish when a group or column is removed.
- Projection presented as a promise or used to block enrollment/clearance.
- Reports slow down fee-week collection work.

### Edge cases and what happens then
- No active section → row shows '—', pesos counted.
- No payments yet → "No Collections Yet", no fake line.
- Ledger with no payment → AR shows '—'.
- Refund/correction → stays in totals per existing handling.

## 7. Out of Scope
- Parent auto-reminders (exist in code, untouched), per-student projection rows, directress/principal copy changes, kinder cutoffs, new exports.

## 8. Success Checks
- [ ] Toggle swaps Collections/Receivables keeping each view's filters.
- [ ] Receivables shows AR No. column, no 'Unknown', no Section column, matching collections design.
- [ ] Dashboard strip shows outstanding + Estimate; Projections page opens from the left menu. **\[Superseded 2026-10-05: dashboard per `cashier-dashboard-simplification.md` §8; Projections wording Receivables, no Estimate.\]**
- [ ] Empty states read "All settled" / "No Collections Yet".

## 9. Open Questions (if any)
None — toggle style, flat receivables with AR, pace-based Estimate, wording, and follow-ups all confirmed 2026-10-04. **\[Note 2026-10-05: pace-based Estimate later removed at user's request during build; see `cashier-projection-chart.md` §7 rev.\]**


## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Children (each its own approvable spec): `cashier-reports-toggle.md`, `cashier-receivables-cleanup.md`, `cashier-projection-chart.md`. Build order: toggle → cleanup → projection.
- Related (not in scope): monthly `reminders:payment` (`SendPaymentReminders`, 12th) stays untouched.
- Likely areas: `portal/cashier/reports.blade.php` (tabs today), `partials/receivables-results.blade.php` (grade groups + 'Unknown' at `CashierController:456`), `partials/collections-report-results.blade.php` (design reference), `portal/cashier/dashboard.blade.php`, cashier sidebar menu.
- Data touched: assessed/paid/balance + receipt/AR refs only; projection reads only.
- Roles: cashier-only views, as today.

## 11. Approval
> Approved by user on 2026-10-04 (original). Re-approved by user on 2026-10-05 for supersede notes only ("please do 1 and 2") — scope in §§1–2, 7 unchanged.
