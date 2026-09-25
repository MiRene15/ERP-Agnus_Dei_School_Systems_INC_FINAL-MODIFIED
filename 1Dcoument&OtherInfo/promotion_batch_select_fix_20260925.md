# Promotion Batch Select + Margin Fixes — 2026-09-25

> **Request:** Fix margins; fix batch select — the "select all" box must be different for ALL STUDENTS vs. per-grade-level batch select, because selecting all in Kinder currently selects every student in the system.
> **Status:** MDs updated first per instruction — code execution follows

---

## 1. Current State / Root Cause

- **File:** `resources/views/portal/admin/partials/promotion-index-results.blade.php`
- **Bug 1 — global `document.querySelectorAll('.promo-checkbox')`:** the per-grade table header checkbox calls `toggleAllPromo($event)`, which selects **every** `.promo-checkbox` in the document — i.e. all students in all 13 grade tables, not just the grade whose header was clicked. One checkbox = one shared state.
- **Bug 2 — no global control:** there is no "select all students across all grade levels" box at all; the only checkbox that looks global is the per-grade header one, which caused the confusion.
- **Bug 3 — fragile "Select qualified in {grade}":** it inspects `row.style.display !== 'none'` (inline style, not Alpine `x-show` state) to guess the visible grade, then re-derives `selectedIds` from every checked box. Breaks when the grade filter tabs hide other grades or when the row filter pills (`qualified`/`not`/`none`/`balance`) hide rows.
- **Bug 4 — header state never syncs:** per-grade header checkbox and row checkboxes have no two-way sync, so a header checkbox can stay checked while its rows are unchecked (or vice versa), and no indeterminate state.
- **Margins/spacing:** table cells use `px-2` with an unconstrained `w-full` table, so columns collapse and headers/rows misalign; grade cards stack with inconsistent `mb-*`; the sticky batch bar has no bottom spacing when pinned.
- **Bonus bug — nested form:** the bottom "Batch N selected" form sat **inside** the "Process All Actions" `<form>` (invalid HTML — browsers drop the inner form, so that batch button never submitted a real request). Batch controls now live only in the sticky top bar (outside the form), and the bottom row keeps the audit-log link + Process All Actions button. The school-year `<select>` is bound with `x-model="schoolYear"` so the sticky batch form always submits the currently chosen year (previously it read the DOM once at render time and could submit the wrong year).

---

## 2. Changes

### 2.1 Scoped selection state (`x-data` on outer wrapper)

| Function | Behavior |
|----------|----------|
| `boxes(grade)` | returns only row checkboxes carrying `data-grade="{gradeLevel}"` |
| `setGrade(grade, checked)` | checks/unchecks **only** that grade's rows, merging/removing ids from `selectedIds` |
| `selectQualified(grade)` | checks only rows in that grade with `data-qualified="1"` |
| `setAll(checked)` | the new global "Select all students" box — all grades, all rows |
| `selectAllQualified()` | global qualified-only shortcut |
| `clearSelection()` | unchecks everything, resets both global + per-grade headers |
| `syncHeaders()` | per-grade header `checked`/`indeterminate` + global header `checked`/`indeterminate` derived from actual row states |

### 2.2 Distinct controls (no shared checkbox)
- **Global:** one `data-global-select` checkbox in the toolbar labeled **"Select all students (all grade levels)"** + "Qualified only (all)" + "Clear selection".
- **Per grade:** `data-scope-header="{gradeLevel}"` checkbox scoped to its own table + "Select qualified in {grade}" + "Clear {grade}" links.
- Every row checkbox: `data-grade="{gradeLevel}"` + `data-qualified="{0|1}"`, `@change="syncHeaders()"`.

### 2.3 Margins / layout
- Single spacing scale: page block `mb-6`, grade filter tabs `mb-4`, toolbar `mb-5`, grade cards `mb-5`, sticky batch bar `mb-4` with `top-2`.
- Table cells unified to `px-4 py-3` (header + body) so the checkbox column and columns line up; `min-w-[960px]` inside `overflow-x-auto` so columns stop collapsing; grade/section cells `whitespace-nowrap`.
- Action column: `w-44` select + full-width reason input; reason input `mt-1`.
- Footer row: `mt-6 pt-4 border-t` so the "Process All Actions" button is not glued to the last card.
- Status badges and the "No grades"/"Qualified" pills keep consistent `px-2 py-0.5` sizing.

---

## 3. Files Changed

| File | Change |
|------|--------|
| `resources/views/portal/admin/partials/promotion-index-results.blade.php` | scoped Alpine state, separate global + per-grade select controls, synced/indeterminate headers, removed invalid nested bottom batch form, school-year bound via `x-model`, margin + table alignment pass |
| `resources/views/portal/admin/promotion/index.blade.php` (wrapper) | none — no change needed (partial re-renders whole table) |

No controller/route change: `batchPromote()` already accepts `enrollment_ids[]` and skips non-qualified students server-side.

---

## 4. Verification

- `php -l` on controller (unchanged), `php artisan view:cache` to compile all blades.
- `npm run build` only if `resources/js/app.js` changes — it does **not** for this fix (state is local to the partial), so no rebuild required.
- Manual: select-all in Kinder → only Kinder rows checked; global select-all → all grades; batch count reflects only that grade.
