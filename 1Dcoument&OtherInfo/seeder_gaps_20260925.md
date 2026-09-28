# Seeder Gap Audit + Fixes — 2026-09-25

> **Request:** "Check if I updated the seeders from the new addition of more information/accounts into the system for better visibility" — audit + fix.
> **Status:** ✅ Executed — MDs written first per instruction

---

## 1. Audit result

### Covered ✓ (prior commit `5c2ca91` + older)
- **All 9 roles have seeded logins** (password `Agnus2026!`): Admin, Registrar, Cashier ×2, Teacher ×20, Librarian, Nurse (role 6), Student ×75, Directress (8), Principal (9)
- ClinicLog (20 visits), Books/transactions, FeeSchedules, StudentLedger (`full`/`installment` + discounts), grade strands/electives, announcements, grades/assessments, sections (saint/virtue names), settings
- Schema sanity: every seeded column exists in migrations; unique keys match upserts

### Gaps found ✗ → fixed by `AuditLogsAndExtrasSeeder` (new)
| Missing coverage | Page empty on fresh seed |
|---|---|
| `activity_log` | Admin → Audit Logs (empty despite full-audit-coverage batch `0479983`) |
| `graduation_fees` + `student_graduation_fees` | Graduation Fees matrix (directress) |
| `withdrawals` | refund flow |
| `inquiries` | promo-site inquiries |
| `library_visits` | librarian visit log |
| `requirements` | admission document uploads |
| `locked_school_years` setting | no locked-year demo state |

### Bugs found ✗ → fixed
1. **`AnnouncementsTableSeeder` used `Announcement::create()`** — the only non-idempotent seeder; appended 4 duplicate rows every re-run → now `updateOrCreate`.
2. **`LibraryAndClinicSeeder` wrote 2 non-existent columns** (`actual_return_date`, `late_days`, not in any migration nor `Model::$fillable`) — silently discarded; late-return demo data never persisted → removed; lateness is computed from `return_date`/`returned_at` anyway.

## 2. Changes

### `database/seeders/AuditLogsAndExtrasSeeder.php` (new, idempotent)
- ~30 `activity_log` rows spanning the real event vocabulary (Logged In, Payment Recorded, Receipt Voided, Account Confirmed, Exported, Grade Updated, Enrollment Approved, Settings Updated, Login Failed, etc.) with **causers** (admin/cashier/registrar) and **morph subjects** (Student, Payment, User) so the audit-log page's search, causer filter, and event filter all have visible data
- Graduation fees (per grade) + per-student assignments (paid/unpaid mix)
- Withdrawals, promo inquiries, library visits, admission requirements
- `locked_school_years` setting seeded as `[]` (explicit default)
- All `updateOrCreate`/`firstOrCreate` → safe to re-run

### `database/seeders/AnnouncementsTableSeeder.php`
- `Announcement::create(...)` → `Announcement::updateOrCreate([...id key...], ...)`

### `database/seeders/LibraryAndClinicSeeder.php`
- Removed phantom `actual_return_date` / `late_days` fields

### `database/seeders/DatabaseSeeder.php`
- Registered `AuditLogsAndExtrasSeeder` last (after announcements)

## 3. Verification
- `php -l` on all 4 seeder files; `php artisan db:seed --class=AuditLogsAndExtrasSeeder` **not run** against live Supabase DB without explicit OK.

> **Superseded note (later same day):** library visits are now seeded in `LibraryAndClinicSeeder` (expanded to ~35, deterministic) — see `clinic_library_processes_20260925.md`.

> **Live-run fix (execution against Supabase):** `seedRequirements()` originally wrote `file_path`, but migration `2026_08_14_120000_switch_requirements_to_bytea` **drops `file_path`** (content now lives in nullable `file_content::bytea`). Fixed to the current schema: `admission_id, document_type, original_filename, mime_type, file_size, status` (nullable `file_content` — the download UI already guards `if (!$requirement->file_content)` at `StudentAdmissionController:331`).
