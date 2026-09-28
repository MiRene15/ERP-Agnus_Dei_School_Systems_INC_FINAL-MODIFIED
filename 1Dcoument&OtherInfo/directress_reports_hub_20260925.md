# Directress Reports Hub — 5 Tabbed Reports + CSV Exports — 2026-09-25

> **Request:** Build reports for the directress with the same structure as the cashier's Collections/Receivables reports. Decided scope: unified tabbed **Reports hub** with 5 tabs (Collections, Receivables, Clinic, Library, Student Statistics), each with its own CSV export; fold existing Cashier Reports + Library Reports pages into the hub (routes redirect).
> **Status:** ✅ Executed — MDs written first per instruction

---

## 1. Design (mirrors `portal/cashier/reports.blade.php`)

- **Wrapper** `portal/directress/reports.blade.php` — tab bar + `?tab=` preselect (used by redirects), each tab hosts its own `ajaxTable` + filter form + Export CSV button bound to active filters.
- **Tab endpoints** — each follows the `isAjax ? response()->json(['html' => partial]) : wrapper` pattern from `DirectressController::cashierReports()`.

## 2. Routes (`routes/web.php`, inside `role:8` group)

| Route | Method | Name |
|---|---|---|
| `/directress/reports` | `reports` | `directress.reports` |
| `/directress/reports/collections` | `collectionsReport` | `directress.reports.collections` |
| `/directress/reports/receivables` | `receivablesReport` | `directress.reports.receivables` |
| `/directress/reports/clinic` | `clinicReport` | `directress.reports.clinic` |
| `/directress/reports/library` | `libraryReports` | `directress.reports.library` |
| `/directress/reports/students` | `studentStatsReport` | `directress.reports.students` |
| `/directress/reports/receivables/export` | `exportReceivablesReport` | `directress.reports.receivables.export` |
| `/directress/reports/clinic/export` | `exportClinicReport` | `directress.reports.clinic.export` |
| `/directress/reports/students/export` | `exportStudentStatsReport` | `directress.reports.students.export` |

- Old `directress.cashier-reports` → **redirect** `directress.reports?tab=collections`; old `directress.library-reports` → redirect `?tab=library`.
- Old export routes kept as-is and reused: Collections tab → `directress.cashier-reports.export`; Library tab → `directress.library-reports.export`.

## 3. Tabs

1. **Collections** — restructured from `cashierReports()`: summary strip (Total Collected / Receipts / By Payment Plan) + **daily breakdown** tiles + payments table with **tfoot TOTAL row** + date-range filter. *Dropped the Chart.js monthly trend — `x-html`-injected `<script>` tags never execute, so that canvas was silently broken; daily breakdown replaces it and matches the cashier structure.*
2. **Receivables** — `StudentLedger` `balance > 0` grouped by active grade level; Total Receivable / Students-with-Balance cards + per-grade tables + tfoot totals.
3. **Clinic** — `ClinicLog` date-range: visits, unique patients, by-grade counts, top symptoms/diagnosis, referrals-out, recent-visits table.
4. **Library** — existing `libraryReports()` data (books/copies/overdue/fines + recent + popular) as a tab.
5. **Student Statistics** — demographics queries (`byGrade/bySection/byYear/byGender/byStrand`, totals) as stat tables + CSV.

## 4. Exports (all CSV stream + `log_activity(..., 'Exported', ...)`)

Collections → existing `exportCashierReports` · Library → existing `exportLibraryReports` · Receivables/Clinic/Students → **3 new methods** (`exportReceivablesReport`, `exportClinicReport`, `exportStudentStatsReport`).

## 5. Files

| File | Change |
|---|---|
| `app/Http/Controllers/Portal/DirectressController.php` | `reports()` + 4 new tab methods; restructure `cashierReports` → `collectionsReport`; +3 export methods |
| `routes/web.php` | 9 new routes, 2 old routes → redirects |
| `resources/views/portal/directress/reports.blade.php` | **new** tabbed wrapper |
| `partials/collections-report-results.blade.php` | evolved from `cashier-reports-results` (summary strip + daily + tfoot) |
| `partials/receivables-results.blade.php`, `clinic-reports-results.blade.php`, `student-stats-results.blade.php` | **new** |
| `partials/library-reports-results.blade.php` | kept, export button → new tab route |
| `partials/sidebar-directress.blade.php` | "Library Reports" + "Cashier Reports" → single **Reports** link |
| Deleted | `directress/cashier-reports.blade.php`, `directress/library-reports.blade.php` |

## 6. Verification
- `php -l` on controller, `php artisan route:list --name=directress`, `php artisan view:cache`.
