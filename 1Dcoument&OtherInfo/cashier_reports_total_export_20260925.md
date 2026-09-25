# Cashier Reports: Total Collections + Dual Export — 2026-09-25

> **Request:** For the collections report, add the total collections (not just the daily). Make sure BOTH collections and receivables can be exported.
> **Status:** ✅ Executed — MDs written first; `php -l` clean, route registered, `view:cache` clean

---

## 1. Current State

| Piece | Problem |
|-------|---------|
| `portal/cashier/reports.blade.php` (Reports tab) | Collections tab shows **only** the daily-breakdown tiles + payment table — **no total** anywhere. No Export button either. |
| `portal/cashier/collections-report.blade.php` (standalone) | Summary cards (Total Collected / Receipts / By Plan) sit **outside** the AJAX area → they go **stale** whenever the date filter changes; the injected partial never updates them. Export link uses `$dateFrom/$dateTo` from the initial render, so it exports the **old** range after the user changes dates. |
| `CashierController::collectionsReport()` (AJAX branch, line ~422) | Passes only `payments` + `dailyBreakdown` to the partial — `$totalCollected`, `$receiptCount`, `$byPlan`, `$dateFrom/$dateTo` never reach the injected content. |
| Receivables | **No export route/method/button exists at all.** |

## 2. Changes

### 2.1 Total collections in the report body
- `CashierController::collectionsReport()` — AJAX branch now passes `totalCollected, receiptCount, byPlan, dateFrom, dateTo` alongside `payments, dailyBreakdown`.
- `partials/collections-report-results.blade.php` — new **summary strip at the top**: Total Collections (₱, 2dp) · Total Receipts · By Payment Plan (count + ₱ per plan), all dark-mode aware; plus a **`<tfoot>` TOTAL row** (receipts count + ₱ total) at the bottom of the payments table.
- `collections-report.blade.php` — the stale static summary grid is **removed** (summary now lives inside the AJAX partial, so it refreshes with the dates).

### 2.2 Export — collections
- Export CSV link becomes Alpine-driven: `:href` built from `filters.date_from` / `filters.date_to`, so the exported range always matches the visible report (fixes the stale-range bug).
- Added an **Export CSV** button to the Collections tab in `reports.blade.php` (previously had none).

### 2.3 Export — receivables (new)
- `CashierController::receivablesReportExport()` — CSV: Grade, Student, LRN, Section, Balance, grouped by grade, ending with a **TOTAL** row; `log_activity(Payment::class/student ledger, 'Exported', ...)` audit entry.
- Route: `GET /cashier/reports/receivables/export` → `cashier.reports.receivables.export`.
- **Export CSV** button on the Receivables tab in `reports.blade.php`.

## 3. Files Changed

| File | Change |
|------|--------|
| `app/Http/Controllers/Portal/CashierController.php` | `collectionsReport()` AJAX summary vars; new `receivablesReportExport()` |
| `routes/web.php` | `cashier.reports.receivables.export` route |
| `resources/views/portal/cashier/partials/collections-report-results.blade.php` | summary strip + tfoot total row + dark mode |
| `resources/views/portal/cashier/collections-report.blade.php` | remove stale summary grid; dynamic export href |
| `resources/views/portal/cashier/reports.blade.php` | Export CSV buttons on both tabs (collections dynamic, receivables static) |

## 4. Verification
- `php -l` on `CashierController.php`, `php artisan route:list --name=cashier`, `php artisan view:cache`.
