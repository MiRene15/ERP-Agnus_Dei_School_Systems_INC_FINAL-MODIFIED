# Spec: Seed Data Payments Fulfilled

- **Status**: Implemented
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-11
- **Implemented**: 2026-10-11 — Slices 1–4 implemented (FixArNumbersSeeder year-scoped, PaymentsHistorySeeder history+current+carry, DatabaseSeeder order). Acceptance checks NOT run — no verification output was provided; closed at the user's explicit direction without confirmed passes. Re-run the Phase 5 checks before relying on this in production.

## 1. Why We Need This
Cashier reports look empty because payments only exist for the current year with today's dates. This fills believable paid-up history for 2024-2025 and 2025-2026 plus a ~99% paid current year — with scattered receipts — so collected totals, receivables, and projections all demo true.

## 2. Who Is Affected
* **Cashier (primary)** — receipts, collected vs still-to-collect, and projections read full per year.
* **Parents / Students (read-only)** — statements show installments with receipts per year.
* **Principal / Registrar** — paid/owing flags and holds read correctly per year.
* **IT Admin** — runs once; re-runs never double-charge.

## 3. Business Flow: Today vs After
- **As-is**: Payments built for the active year only, dates today, some ledgers without receipts.
- **To-be**:
  1. 2024-2025: every enrollment billed from fee tables → paid 100% (minus 2–3 demo debtors) in 2–4 scattered installments June 2024–Feb 2025 with unique ORs and ARs → ledgers zeroed (debtors keep balance) → locked.
  2. 2025-2026: same, 100% minus demo debtors → locked. Paid kids promote carrying zero; demo debtors promote carrying their balance forward.
  3. Current: billed, ~99% paid with the same installment shape scattered to today; demo debtors show live receivables → stays unlocked.
  4. 2027-2028: fees ready from Child 1, zero payments.
- **Preserved**: School-year lock (locked years refuse new money plainly). One ledger per student; promotion carry math unchanged (old balance → carried-over + new fees). Audit keeps cashier + timestamp per receipt.
- **Exceptions**: Locked-year payment attempts refused with no partial write; duplicate OR/AR never written; discounts/scholarships applied once.

## 4. How It Should Work
1. IT Admin runs starter data (after Children 1–2) → ledgers billed → installments paid with OR + AR, method, cashier, scattered paid dates.
2. Cashier opens money reports or statements → switches 2024 → 2025 → current → sees collected-full, collected-full, ~99% + receivables.
3. Sample debtor's statement shows balance carrying to next year's ledger with clearance Uncleared; paid kids show Cleared with carried-zero.
4. Re-run → existing receipts kept, ledgers never double-charged.

## 5. Look & Feel (UX)
- Where it lives: no new screen. Cashier hub, parent statements, reports hub. Primary check: year switch shows the right collected/receivables split.
- Key states: default (current pre-selected, ~99% + receivables); history (paid-up, printable receipts); ready (2027-2028 zero payments, plain note); error (locked-year payment refused plainly); permission-denied (unchanged roles).
- Receipts print OR + AR numbers, method, cashier, and date. Plain language, fewest clicks.

## 6. Business Rules
### Must always be true
- Every payment joins a ledger + enrollment of the same school year with scattered weekday paid dates in window.
- Every payment has a unique receipt number + year-scoped AR (`AR-YYYY-NNNN`), method, and cashier.
- 2024-2025/2025-2026 fulfilled minus demo debtors and locked; current ~99% paid and unlocked; 2027-2028 zero payments.
- Demo debtors (2–3 per year) carry balance forward on promotion with clearance Uncleared; paid kids carry zero with clearance Cleared.
- Re-runs keep existing receipts and never add a second charge for the same installment.

### Must never happen
- A payment without a ledger or enrollment, or stamped to the wrong year.
- A repeated OR or AR number across any year.
- A locked year accepting new money.
- A discount/scholarship reducing the same due twice.
- 2027-2028 carrying payments before enrollment opens.

### Edge cases and what happens then
- Re-run finds the OR/AR → row kept, ledger untouched.
- Installment for a locked year → refused whole, nothing half-written.
- Debtor promotes → old balance stored as carried-over, new fees added on top.
- Backfill finds a missing AR → assigns the next `AR-YYYY-NNNN` in that payment's year, never today's year.

## 7. Out of Scope
- Changing fee amounts, discount rules, or promotion carry math.
- New receipt layouts, e-receipt emails, installment templates, reminder logs (noted follow-ups).
- Grades, attendance, library, clinic (Child 4); backbone, students (Children 1–2).

## 8. Success Checks
- [ ] 2024-2025 and 2025-2026 show fulfilled collected totals with printable ORs/ARs per installment.
- [ ] Current shows ~99% collected plus visible receivables from demo debtors.
- [ ] Every payment shows unique OR + AR, method, cashier, and a scattered date in its year.
- [ ] Sample debtor's balance visibly carries to next year's ledger (Uncleared); paid kids carry zero (Cleared).
- [ ] Locked years refuse new payments plainly; re-run double-charges nothing.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: cashier hub, parent statements, receipt print, reports hub, year pickers (no layout change).
- Likely areas of the codebase (from code inspection):
  - `Actual_Website/ERP_Agnus_Dei_School_Systems_INC/database/seeders/StudentsAndFeesSeeder.php` + `StudentScatterSeeder.php:200-277` — ledger billing + installment loop must run per `school_year` with year-window scattered `paid_at`; keep `updateOrCreate(ledger)` + idempotent payment keys on `(ledger, receipt_number)`.
  - `.../seeders/FixArNumbersSeeder.php:12-18` — writes `AR-YYYY-0500+`; Child 3 writes ARs correctly per payment year up front (backfill becomes no-op).
  - `app/Services/PromotionService.php:259-284` — `carryFees()` carry math unchanged; fulfilled promotes carry 0, debtors carry balance.
  - `app/helpers.php:84-92` — `school_year_locked()` refuses locked-year money; cashier controllers already enforce it.
- Data/records touched: ledgers (assessed, balance, carried-over, clearance), payments (receipt/AR numbers, method, cashier, paid dates) — each stamped to its school year.
- Parent: `seed-data-connected-history.md` (Approved). Previous: `seed-data-students-promotions.md` (Approved). Next: `seed-data-grades-attendance-support.md`.
- Roles/permissions involved: none changed.

## 11. Approval
> Approved by user on 2026-10-11.
