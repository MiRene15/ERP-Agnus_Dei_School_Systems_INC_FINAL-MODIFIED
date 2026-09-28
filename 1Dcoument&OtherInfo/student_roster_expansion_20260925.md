# Student Roster Expansion — 75 → 165 Students — 2026-09-25

> **Request:** "What about the students? Do I have 100 to 200 students with linked information across the system for better visibility?"
> **Answer before change:** No — 75 students (from `StudentsAndFeesSeeder::$studentSeeds`, curated list, bumped 42 → 75 in `5c2ca91`).
> **Status:** ✅ Executed — MDs written first per instruction; target **165** (within 100–200)

---

## 1. What each seeded student gets (existing `run()` loop, reused as-is)

user account (role 7, `Agnus2026!`) → student profile (LRN, parents, addresses, DOB by grade, archive fields) → admission (SY, grade, strand) → enrollment (section, incl. strand section for SHS) → `enrollment_subject` pivot (all classes) → fee-schedule-based StudentLedger (plan, discounts, balance, clearance) → 1–2 Payments (random dates) → **grades/assessments** (`GradesAssessmentsSeeder` iterates ALL active enrollments ✓) → graduation fees (Grade 10/12, `AuditLogsAndExtrasSeeder`) → withdrawals for Withdrawn/Transferred enrollments.

## 2. Change — `StudentsAndFeesSeeder`

- New `generateRoster(int $count): array` appends **90 programmatic students** to the curated 75 → **165 total**:
  - Cycles Kinder → Grade 12 evenly (~7/grade); SHS gets strands cycling STEM/ABM/HUMSS/GAS
  - Unique first/last-name pair grid (30 × 30 pool); email collisions auto-suffixed by existing `resolveEmail()`
  - Status variety: ~4 graduated (Grade 12), ~5 withdrawn, ~5 transferred → archives + Withdrawals table populated
  - Scholarship (ESC) every 9th
- Payment-behavior match extended for `$index >= 75` so **balances spread across the whole roster** (not just the first 75): fully-unpaid, fully-paid, half-paid patterns every 14th index, default 30–60%
- Discount chain extended: honor (10%) / sibling (5%) patterns for generated indexes

## 3. Ripple checks (why nothing else breaks)

| Consumer | Behavior with 165 |
|---|---|
| `GradesAssessmentsSeeder` | iterates all active enrollments → ~3× more grade rows (chunked upsert ✓) |
| `LibraryAndClinicSeeder` / `AuditLogsAndExtrasSeeder` | random student picks — richer data |
| Cashier/Directress reports, receivables | bigger tables + wider balance spread ✓ |
| Email collisions | `resolveEmail()` counter suffix handles duplicate names ✓ |
| Index-based demo rules (first 75) | untouched — new rules gated on `$index >= 75` |

## 4. Verification
- `php -l database/seeders/StudentsAndFeesSeeder.php`. **Seeder not executed** against live Supabase DB without explicit OK.
