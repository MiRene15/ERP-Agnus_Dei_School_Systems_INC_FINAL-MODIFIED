# Polish Suggestions — Low-Effort, High-Defense-Value

> Pick 3-5 for max ROI before defense. All are <2h each. Grouped by effort.

## Quick Wins (<30 min each)
1. **Enrollment Closed gate** — `StudentAdmissionController@create/store` check `Setting::getValue('enrollment_open')`. If `0`, show centered "Enrollment is currently closed" card on `admission-apply.blade.php` and block POST with `with('error')`. Defends `enrollment_open` setting you just added.
2. **Passing grade actually used** — Wire `passing_grade` setting into Report Card remarks and Promotion qualified check (today hardcoded 75). One-line change: `$passing = (int) Setting::getValue('passing_grade','75')`. Shows settings are real.
3. **Empty states copy** — Every `No records` → add one-line hint: "Try adjusting filters or create via [+ Add]". Already have skeletons; just text.
4. **Favicon + title** — Add school logo as favicon in `portal/layouts/app.blade.php` and `<title>{{ $title ?? 'Agnus Dei ERP' }}</title>` per page. Looks finished.
5. **Consistent date format** — Ensure all dates use `M d, Y` (or `Y-m-d`) via a helper; search for stray `->format('Y/m/d')` and unify. Panel notices inconsistency.
6. **Breadcrumbs home link** — All portal pages already have `Home > Dashboard > Current`; just ensure `dashboard` routeIs active highlight we fixed stays.

## Small Features (30-90 min each)
7. **Subjects CSV import (Admin, hybrid like schedules)** — Reuse `PrincipalController@schedulesImport` logic for `Admin/SubjectController@import`. Columns: `name,category,grade_level`. Same template download + preview + skip duplicates. Mirrors the hybrid you just approved; shows pattern reuse.
8. **Promotion filter chips** — Add client-side filter on `promotion-index-results.blade.php`: `All | Qualified | Not qualified | No grades | With balance` (JS filter, no backend change). Helps demo.
9. **Per-student grade breakdown tooltip/modal** — On promotion row, small `View` link opens modal with `grades` list (`subject — final_grade`) from already eager-loaded `grades` + `subjects`. No new query.
10. **Library overdue badge** — In `librarian/loans` table, show `Overdue X days` badge when `due_date < today && return_date null`. Uses existing `overdue` logic; just badge.
11. **Audit log deep link** — In promotion success message, link to `admin.audit-logs?event=Promoted` so panel can verify.
12. **Export row count in filename** — `Enrollments_20250820_23rows.csv` — one-line in `ExportController`. Shows attention to detail.

## Docs / Defense Polish (no code)
13. **Capstone ERD refresh** — Update `capstone_master_document.md` ERD to include `settings` and correct `enrollments.status` values (Transferred/Dropped) and `grades` weights 20/20/20/40. One diagram.
14. **Section IX sync** — Ensure Directress module in doc no longer lists Teachers (we moved to Admin) — already done but double-check after Verification rename.
15. **Demo seed data** — Add 2 failing students (`GWA 68` with 2 fails) + 1 no-grades new enrollee so promotion Qualified/Not qualified/No grades all show during demo.

## Not Recommended Now (higher risk)
- Full RBAC matrix page, real-time notifications, dark mode — nice but needs >1 day and can introduce bugs before defense.

## Suggested Next 3 (do these first)
If time is short, do **1 + 2 + 7** — they directly prove your new settings + hybrid CSV pattern are not just docs.
