# Spec: Cashier Payments Year Filter

- **Status**: Approved
- **Created**: 2026-10-12
- **Approved by**: user on 2026-10-12

## 1. Why We Need This

The School Year picker on Process Payments looks like a filter but doesn't narrow the student list — it only changes the fee math behind the scenes. The cashier can open and charge a student from the wrong school year, especially during enrollment season when two years' students look identical. This fix makes the picker truthful: pick a year, see only that year's students, and always charge that year's schedule.

## 2. Who Is Affected

- **Cashier (primary)** — a clean queue per year; no more self-checking which year each row belongs to.
- **Registrar** — reconciles the same year the cashier collected; fewer mismatches.
- **Directress** — year-clean collection reports.
- **Families and students** — never charged last year's schedule by mistake.
- **Librarian, Nurse, Teachers, IT Admin** — untouched; no new permissions.

## 3. Business Flow: Today vs After

- **As-is**: cashier picks a school year and searches → the list still mixes years (the picker only changes fee totals) → cashier must eyeball each row's year before clicking Take Payment.
- **To-be**:
  1. Cashier picks a school year → the list shows only that year's students.
  2. Cashier searches within that year → results stay in that year.
  3. Cashier clicks Take Payment → the form opens in that year's context with that year's totals.
  4. Picking a year with no students shows "No students in {year} — try another year or check enrollment."
  5. Picking a locked or old year shows its students read-only — no new payment can post.
- **Preserved**: how money posts, discount approvals, receipt numbering, audit trail, protection against double-press, school-year locks, library dues sharing one receipt, Fully Paid gating, forwarded files reaching the cashier before enrollment.
- **Exceptions**: transfers appear only in their current enrollment year; corrections and voids keep working as today; overpayments credited forward read as today; forwarded files with no enrollment appear only under their admission year.

## 4. How It Should Work

1. Cashier opens Process Payments → the picker defaults to the current school year and the queue shows only that year.
2. Cashier picks a different year → the queue refreshes to that year only.
3. Cashier searches by name, student number, or LRN → results stay within the picked year.
4. Forwarded files (no enrollment yet) appear only when their admission is in the picked year.
5. A year with no students shows the empty message with what to do next.
6. Take Payment from the queue opens with the picked year's fee totals; locked years stay read-only.

## 5. Look & Feel (UX)

- Where it lives: the same Process Payments screen. Picker stays left of Search. One primary action: Search.
- What the cashier sees first: year picker, then search box, then the year-only list.
- Key states: default year-only list; loading shimmer while searching; empty ("No students in {year} — try another year or check enrollment"); error with Refresh staying as today; success (payment posts) unchanged.
- Plain-language labels: School year, Search, Take Payment, No students in {year}. All pesos shown the same way (₱1,234.56).
- Fewer clicks: nothing new to fill in; picking the year is enough — no per-row year checking.
- Peak-load note: enrollment and fee-due days — filtering by year must make the list faster, never slower.

## 6. Business Rules

### Must always be true

- The queue shows only students of the picked year: an Active enrollment in that year, or a forwarded file whose admission is in that year.
- The fee totals shown always come from the picked year's schedule.
- Forwarded files with no enrollment appear only under their admission year — never in every year.
- Locked school years never accept new payments; everything stays read-only.

### Must never happen

- A student from another year appearing in the picked year's queue.
- Charging one year's schedule while standing in another year's queue.
- A forwarded file vanishing from all years or appearing in all years.
- A display fix changing how much money posted, which discount applied, or whether an approval is granted.

### Edge cases and what happens then

- Transfer mid-year → appears only in the new enrollment year from then on.
- Student with enrollments in two years → appears in each year's queue under that year's enrollment; each payment posts to the opened year's account and history stands.
- Forwarded file, no enrollment → only under its admission year.
- Fully Paid old year → Fully Paid message, no payment form, as today.
- Locked school year → students list but no new payment can post, as today.
- Overpayment credited forward, library dues on one receipt → behave exactly as today, just within the picked year.

## 7. Out of Scope

- Changing fee amounts, discount math, receipt math, or approval rules.
- Rewriting year-scoped financial history or ledgers.
- Other roles' screens (registrar, librarian, nurse, teacher, principal, directress).
- Printed receipt restyling.
- Online payments or auto-debit.
- Remember-last-year, per-row year badges, reports year filter (parked follow-ups).

## 8. Success Checks

- [ ] Picking 2025–2026 shows only that year's students.
- [ ] Picking another year changes the queue; the previous year's students are gone.
- [ ] A forwarded file appears only under its admission year.
- [ ] Month and fee totals match the picked year's schedule.
- [ ] A year with no students shows "No students in {year}."
- [ ] A locked year is read-only — no new payment posts.

## 9. Open Questions (if any)

None — all interview topics confirmed; parked follow-ups (remember-last-year, row badges, reports filter) are out of scope.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: Process Payments queue only — `portal/cashier/payments.blade.php` (picker + empty state), served by `GET /cashier/payments` (page) and `GET /cashier/search?search=…&school_year=…` (live search); Take Payment popup and money paths untouched.
- Likely areas of the codebase (files, routes, tables) — from read-only inspection 2026-10-12:
  - Year input is accepted but never constrains the queue: `CashierController@payments` and `@searchStudents` read `school_year` (defaulting to the active year) and use it only for the per-grade fee-basis lookup; the student query filters status + search text only, and the Active enrollment / latest admission are picked regardless of year.
  - Constrain the queue to the picked year: Active enrollment in picked year OR admission in `Awaiting Downpayment` in picked year; keep fee basis = picked year; keep `showPayment` year resolution (enrollment year, else admission year, else active year) and the locked-year guard in `processPayment` as today.
  - Helpers already available: active-year default, full year list, locked-year check (`app/helpers.php`); no new stored fields.
- Data/records touched: read-only filtering; no new or changed stored records.
- Roles/permissions involved: cashier (uses filter); registrar/directress read the same year later; no permission-model change.

## 11. Approval

> Approved by user on 2026-10-12.
