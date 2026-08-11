# Login & Student Dashboard Fixes — Agnus Dei School ERP

Logged: 2026-08-11. Post-deploy fixes found while testing the live Render deployment (`https://agnusdei school.onrender.com` — corrected: `https://agnusdeischool.onrender.com`).

## Items

1. **Show password option on the login page** — add a toggle so users can reveal the password before submitting.
2. **`first_login_at` not persisted to the database on first login** — the value was always NULL even after a user logged in and completed the first-login password change.
3. **Server error 500 when a student logs in** (reproduced with `juan.delacruz@agnusdei.edu.ph`) — the student dashboard crashes during render.

---

## 1. Show password option on login page

### Problem

The login form only had a `type="password"` input with no way to verify what was typed.

### Fix

Added a "Show password" checkbox beside the password field (`resources/views/auth/login.blade.php`). Toggling it switches the input between `type="password"` and `type="text"` via inline JavaScript.

---

## 2. `first_login_at` not uploaded to database

### Problem

`first_login_at` was always NULL in the database (verified on Supabase for user 29):

```
id=29  email=juan.delacruz@agnusdei.edu.ph  role_id=7  status=active
first_login_at = NULL   last_login_at = 2026-08-11 14:13:53
```

Root cause: `first_login_at` (and `last_login_at`) are **not** in `App\Models\User::$fillable`. `ForceChangePasswordController::update()` calls `$request->user()->update(['password' => ..., 'first_login_at' => now()])` — mass assignment silently drops `first_login_at` because it is not fillable. Result: the value is never written.

### Fix

Added `last_login_at` and `first_login_at` to `App\Models\User::$fillable` so the mass-assignment `update()` persists both timestamps.

---

## 3. Server error 500 when student logs in

### Problem

`POST /login` succeeds and redirects to `/force-change-password` → `/student/dashboard`, but the dashboard renders HTTP 500:

```
Undefined variable $selectedTerm (View: resources/views/portal/student/dashboard.blade.php)
```

Root cause: `dashboard.blade.php` used a server-side PHP `@if($selectedTerm === 'all')` / `@php ... $selectedTerm ... @endphp` while `$selectedTerm` only exists as an **Alpine.js** client-side variable (`x-data="{ selectedTerm: 'all' }"`). Blade tries to evaluate it in PHP at render time → 500.

### Fix

Rewrote the grades table so all term columns are server-rendered and Alpine shows/hides them with `x-show="selectedTerm === 'all' || selectedTerm === @js($period)"`. No PHP reference to `$selectedTerm` remains; the per-term filter still works purely on the client side.

---

## Verification

- Re-tested the full student login flow locally against Supabase: `POST /login` → 302 → `/force-change-password` → 302 → `/student/dashboard` returns **HTTP 200**.
- `first_login_at` now written for user 29.
- All changed files pass `php -l`.
