# Dark Mode Audit & Fix Plan — 2026-09-23

> **Scope:** All portal pages (9 roles) + promotional website (public landing)
> **Issue:** ~85% of blade files have 0 `dark:` classes; hover states have 0 `dark:hover:`; promotional site has shallow dark support
> **Goal:** Every page readable and contrasting in dark mode — text, backgrounds, borders, inputs, hovers

---

## 1. Root Cause

| Area | Problem |
|------|---------|
| `tailwind.config.js` | Missing `darkMode: 'class'` — all `dark:` utilities are dead-code (Tailwind v3 defaults to `media`) |
| `portal/layouts/app.blade.php` | Global `.dark .bg-white` hack only covers `gray-50/100/200`, `red/green/blue/yellow/purple-50/100` + `gray-300..900` text. Missing `amber`, `slate`, `orange`, `emerald`, `blue-200/300`, `white/50`, `gray-300` surfaces |
| Portal blades | ~28 files with 0 explicit `dark:` classes — rely on leaky global hack |
| Hover | 120 `hover:` occurrences, 0 `dark:hover:` — global covers only 4 combos (`hover:bg-white/50/100/200`) |
| Promotional site | `PromotionalWebsite/layout.blade.php` dark override is 5 lines; hero overlay, dropdown, inquiry inputs, footer not themed |

---

## 2. Audit Results — Per File

### Critical (no dark at all, >5 issues each)

| File | Issues | Examples |
|------|--------|----------|
| `portal/teacher/grades.blade.php` | 12+ | `h2.text-gray-900`, `bg-green-50`, `input.border-gray-300`, `hover:bg-blue-100` |
| `portal/teacher/dashboard.blade.php` + partials | 10+ | All cards `bg-white border-gray-100`, selects, `hover:bg-blue-50` |
| `portal/teacher/partials/grade-table-results.blade.php` | 15+ | `bg-amber-50` **not in global map** (glaring), `hover:bg-blue-50/30`, sticky `bg-white` |
| `portal/cashier/discounts.blade.php` | 12+ | 97 lines, 0 dark — modals, inputs, `hover:border-gray-400` |
| `portal/librarian/books.blade.php` | 18+ | 370 lines, 0 dark — modals `bg-white`, pagination `border-gray-300 hover:bg-gray-50` |
| `portal/student/partials/dashboard-results.blade.php` | 15+ | 9× `bg-white` cards, `bg-amber-50` not mapped, `hover:shadow-md` invisible |
| `portal/cashier/dashboard` + partials | 8+ | Stat cards, `bg-green-50` |
| `PromotionalWebsite/welcome.blade.php` | 8+ | `hero-overlay: rgba(255,255,255,0.65)` stays white on dark, dots invisible |
| `PromotionalWebsite/inquiry.blade.php` | 6+ | `border:rgba(0,0,0,0.1)` hard light, error `bg:#fee2e2` no dark |
| `PromotionalWebsite/layout.blade.php` | 7+ | `nav` shadow/border, `dropdown-menu`, `portal-option #f1f5f9`, `footer` |

### Systemic Patterns

| Pattern | Light | Dark Should Be |
|---------|-------|----------------|
| Page titles | `text-gray-900` | `dark:text-[#E8EAF6]` |
| Muted text | `text-gray-500/600` | `dark:text-[#8A90B0]` / `dark:text-[#C1C4DC]` |
| Cards | `bg-white border-gray-100` | `dark:bg-[#1A1E3B] dark:border-[#2A2F58]` |
| Subtle bg | `bg-gray-50` | `dark:bg-[#161A33]` or `dark:bg-[#23274C]` |
| Inputs | `border-gray-300` | `dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6]` |
| Hovers | `hover:bg-gray-100` | `dark:hover:bg-[#23274C]` |
| Colored badges | `bg-green-50 border-green-200` | `dark:bg-[rgba(74,222,128,0.12)] dark:border-[rgba(74,222,128,0.25)]` |
| Amber (missing) | `bg-amber-50 border-amber-200` | `dark:bg-[rgba(251,191,36,0.12)] dark:border-[rgba(251,191,36,0.25)]` |
| Table headers | `border-gray-200 text-gray-600` | `dark:border-[#2A2F58] dark:text-[#8A90B0]` |
| Table rows | `border-gray-100 hover:bg-blue-50` | `dark:border-[#2A2F58] dark:hover:bg-[rgba(96,165,250,0.08)]` |

---

## 3. Fix Plan

### Phase A — Config + Global (must do first)

1. **`tailwind.config.js`** — add `darkMode: 'class'`
2. **`portal/layouts/app.blade.php` global CSS** — extend `.dark` overrides:
   - Add `bg-amber-50/100`, `bg-orange-50`, `bg-slate-50`, `bg-white/50`
   - Add `border-amber-200/300`, `border-slate`, `border-blue-200`
   - Add `hover:bg-blue-50`, `hover:bg-indigo-700`, `hover:border-gray-400`
   - Fix `sidebar-link:hover` dark variant
   - Fix skeleton `linear-gradient` dark

### Phase B — Portal Pages (systematic)

For each blade, add explicit `dark:` classes alongside existing light classes (non-breaking — light stays same):

- **Headers:** `text-gray-900` → `text-gray-900 dark:text-[#E8EAF6]`
- **Muted:** `text-gray-500` → `text-gray-500 dark:text-[#8A90B0]`
- **Cards:** `bg-white border-gray-100` → `bg-white dark:bg-[#1A1E3B] border-gray-100 dark:border-[#2A2F58]`
- **Inputs:** `border-gray-300` → `border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6]`
- **Hovers:** `hover:bg-gray-100` → `hover:bg-gray-100 dark:hover:bg-[#23274C]`
- **Modals:** `bg-white rounded-lg` → `bg-white dark:bg-[#1A1E3B]`

Files in priority order:
1. `portal/layouts/app.blade.php` (sidebar/header)
2. `portal/teacher/*` + `portal/student/*` + `portal/cashier/*` (most used)
3. `portal/librarian/*` + `portal/nurse/*` + `portal/registrar/*`
4. `portal/admin/*` + `portal/directress/*` + `portal/principal/*` + `portal/partials/sidebars`

### Phase C — Promotional Website

- **`PromotionalWebsite/layout.blade.php`** — add `html.dark` overrides for: nav shadow/border, dropdown-menu, portal-option, portal-divider, footer, skeleton
- **`PromotionalWebsite/welcome.blade.php`** — hero overlay dark gradient, dot color, announcement cards border/shadow
- **`PromotionalWebsite/inquiry.blade.php`** — input borders/bg, error colors, modal
- All other promo pages (`vision`, `identity`, `educational-philosophy`, etc.) — inherit from layout fix

---

## 4. Verification

- [ ] Toggle dark mode on every portal dashboard — text readable, cards contrasting, no white flash
- [ ] Hover every button/link/card in dark — hover state visible and contrasting
- [ ] Check inputs/selects/textarea in dark — border and bg visible
- [ ] Check modals in dark — not white box on dark bg
- [ ] Check promotional site: hero, nav dropdown, inquiry form, footer in dark
- [ ] Run `npm run build` after `tailwind.config.js` change

---

## 5. Color Reference

| Token | Light | Dark |
|-------|-------|------|
| Card bg | `bg-white` | `dark:bg-[#1A1E3B]` |
| Card border | `border-gray-100` | `dark:border-[#2A2F58]` |
| Subtle bg | `bg-gray-50` | `dark:bg-[#161A33]` |
| Input bg | `bg-white` | `dark:bg-[#23274C]` |
| Input border | `border-gray-300` | `dark:border-[#3B4172]` |
| Primary text | `text-gray-900` | `dark:text-[#E8EAF6]` |
| Secondary text | `text-gray-600` | `dark:text-[#C1C4DC]` |
| Muted text | `text-gray-500` | `dark:text-[#8A90B0]` |
| Hover bg | `hover:bg-gray-100` | `dark:hover:bg-[#23274C]` |
| Amber bg | `bg-amber-50` | `dark:bg-[rgba(251,191,36,0.12)]` |
| Amber border | `border-amber-200` | `dark:border-[rgba(251,191,36,0.25)]` |
| Amber text | `text-amber-800` | `dark:text-[#FCD34D]` |
