# Feature Enhancements — School Systems

Planned feature enhancements for the Agnus Dei School ERP. Generated: 2026-08-06. **Implemented: 2026-08-06**

---

## 1. Library Booking Logs (Borrow/Return Logging)

- **Borrow log** — every student borrow records an entry in `library_transactions` (student, book, librarian, borrow date, due date, status) and auto-decrements the book's `available_quantity`.
- **Return log** — returning a book marks the transaction `Returned` and restores `available_quantity`; capture the actual **return date** (`returned_at` timestamp) alongside the due date.
- **Full history** — librarian can browse all transactions (Borrowed / Returned / Overdue) with filters by status and date, searchable by student name, book title, or serial number.
- **Overdue tracking** — flagged automatically once the due date passes while status is still `Borrowed`.
- **Library visits** — optional time-in/time-out logging per student (`library_visits` model already exists) for visit tracking, separate from book loans.

---

## 2. Overdue & Damage Fees

- **Late return fee** — per-day fine computed from the due date to the actual return date (`returned_at`) once a book comes back overdue; amount configurable.
- **Damage fee** — on return, librarian records the book's **condition** (e.g., Good / Minor Damage / Major Damage / Lost); a fee is assessed when the book is not returned properly and shows damage.
- **Loss fee** — lost book charged the replacement cost (book price or a configured lost-book amount).
- **Condition tracking** — condition logged at borrow time (baseline) and at return time (delta), so only new damage incurred during the loan is charged.
- **Fee assessment** — assessed fees recorded against the student and reflected in the **student ledger** for the cashier to collect, alongside tuition/misc fees.
- **Fee amounts configurable** — per-day fine, damage thresholds, and replacement basis managed in settings.

---

## 3. Book Inactive Logs (Rechecking)

- Librarian can flag books as **Inactive** in addition to the current available/unavailable state.
- New **Inactive Logs** tab listing all deactivated books with reason, date deactivated, and who did it.
- Support **rechecking** — reactivate with a note; log history retained (audit trail).
- Inactive books excluded from borrow dropdown and availability counts.

## 4. Book Serial Numbers

- Add **serial/accession number** to each book (unique per copy, not just per title).
- Editable field on create/edit book; shown in catalog, loans, and printed lists.
- Supports tracking individual copies: each serial = one copy, tied to `available_quantity`.

## 5. AR (Acknowledgment Receipt) Numbers for Cashier

- Receipts currently auto-generate `RCP-YYYYMMDD-####`. Add an **AR number** field.
- **Sequential per school year, starting from 500** → `AR-2026-0500`, `AR-2026-0501`, ... (auto-increment).
- Auto-suggested next AR number; cashier can override within the payment form (unique-checked).
- AR number shown on the payment receipt, payments list, and monthly collections export.

## 6. Optional Manual Attached Receipt

- When processing a payment, cashier may **attach an external receipt file** (image/PDF) — optional.
- Stored with the payment; viewable/downloadable from the payment/ledger view.
- Flag on the payment record when an external receipt was attached.

## 7. Privacy-First Student Search (Cashier)

- Payment screen no longer lists all students upfront.
- Cashier must **search by name first** (with student number/LRN) before seeing any student info.
- Results shown as minimal name/LRN only until a student is selected.

## 8. Miscellaneous Fees

- Keep **one misc fee total** per grade level × term × school year (current structure).
- **Expandable** — each misc fee entry can optionally break into itemized descriptions (e.g., books, uniform, ID) while the total stays as the assessed `misc_fee`.
- Assessed total = tuition + misc fee; reflected in ledger and receipts.

## 9. Student Financial View (Receipt Tab)

- Clicking a searched student opens a **financial tab**: full ledger, payment history with receipts/AR numbers, discounts, balance, and fee breakdown.
- Consolidated view — assessed, paid, due per term, plus attached receipts.

## 10. Gentle Payment Reminder

- **Student portal**: a notice banner showing unpaid balance/upcoming dues (non-intrusive, gentle tone).
- **Email**: auto-scheduled reminder **3 days before the 15th of each month** (i.e., on the 12th) to students with outstanding balance, using existing Gmail SMTP. Missed run on the 12th triggers on the next day.
- Registrar/Admin can also preview or trigger reminders manually.

## 11. Total Collections by Date Range

- Cashier dashboard gains a **filtered collection report**: pick a date range → total collected, number of receipts, breakdown by payment plan, exportable to CSV.
- Replaces/extends the current "today's collection" summary.

## 12. School Year Filters Everywhere

- All school-year-scoped screens (fees, admissions, enrollments, promotions, report cards, exports, payment views) get a **school year filter dropdown**.
- **Past and present** school years selectable — no longer locked to the single `active_school_year()` setting.
- Active year remains the default; dropdown remembers selection.

---

## 13. Portal Dark Mode

- **Toggle** — sun/moon button in the portal header; choice persisted in `localStorage` (`theme`), applied before render to avoid flash.
- **Default** — follows the OS `prefers-color-scheme` on first visit; manual choice overrides it.
- **Scope** — entire portal across all roles (all 78 dashboard views); implemented via a `.dark` CSS override layer in `portal/layouts/app.blade.php` remapping the standard Tailwind classes used by the views (surfaces, text grays, borders, divide, hovers, status colors), plus `color-scheme: dark` for native form controls and a `--navy-text` variable so brand headings stay readable.
- **Excluded** — the login page, which uses the separate `PromotionalWebsite.layout` theme.
