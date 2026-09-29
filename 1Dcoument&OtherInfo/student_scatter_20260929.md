# Student Scatter — 15 Students per Section, All Subjects — 2026-09-29 (Session 55)

**Goal:** every one of the 30 active sections holds **15 active students**, and therefore every active class (subject × section, incl. advisory) has students — fixing the current lopsided distribution where 11 sections are empty and 81 classes have zero students.

## Current distribution (live, 2026-09-29)

- 142 active enrollments / 165 enrolled students (167 rows incl. pre-admission), SY 2026-2027
- 30 active sections — histogram: **0 students × 11 sections** (all `B` sections: Kinder B … Grade 10 B), 1–3 × 6 (all SHS: G12 ABM/GAS/HUMSS, G11 GAS/HUMSS, G12 STEM…), 5–13 × 13 (mostly `A`)
- 215 active classes — **81 with zero students** (= exactly the classes of the 11 empty sections), 37 with 1–4, 97 with 5–14
- Root cause: `StudentsAndFeesSeeder` always picks the **first** section per grade (`->first()`), so `B` sections and low-enumerated SHS strands never received students

## Target

- **15 Active enrollments per section → 450 active (+308 new students)**, every class in every section populated
- Full linkage per new student (mirrors the existing roster pattern):
  - `users` (role 7, password `Agnus2026!`, `@agnusdei.edu.ph` email), `students` (profile, DOB by grade, guardians, scholarship ~10%), `admissions` (Approved By Registrar, strand for SHS), `enrollments` (Active, 2026-2027, correct `strand`), `enrollment_subject` (**all classes of the section**, incl. advisory), `student_ledgers` (FeeSchedule-based assessment, honor/sibling/ESC discount mix), `payments` (paid/half/unpaid mix so cashier reports stay varied)
  - `grades` + `assessments` for every new enrollment (3 terms × all enrolled classes) via **additive re-run** of `GradesAssessmentsSeeder`

## Method

1. **MD first** (this file).
2. New `database/seeders/StudentScatterSeeder.php`:
   - for each active section: `deficit = 15 − current Active enrollments`; generate exactly that many students with section-derived `grade_level` + SHS `strand` (prefix of section name)
   - deterministic names (pooled first/middle/last + per-section slot → unique emails via collision loop), idempotent `updateOrCreate` keys (email / student user_id / admission student+year / enrollment student+year / ledger student)
   - prints per-section before → after summary
3. Fix `GradesAssessmentsSeeder` to be **additive**: skip enrollments that already have grades (today it upserts new random scores into all 3,099 existing rows on every re-run); keep the demo no-grades exclusion intact.
4. Execute live: `db:seed --class=StudentScatterSeeder` → `db:seed --class=GradesAssessmentsSeeder`.
5. Verify: distribution diagnostic → every section = 15, zero-student classes = 0, grades/assessments coverage for all Active enrollments.
6. Re-run live smoke harness (must stay 219/219) — DB grew, harness snapshots are re-taken.

## Row impact (est.)

~308 users + 308 students + 308 admissions + 308 enrollments + ~2,200 enrollment_subject + 308 ledgers + ~250 payments + ~6,600 grades + ~26,400 assessments.

## Rollback

Capture `students.max(id)` / `users.max(id)` before the run; cleanup = delete rows in the new id ranges (students → enrollments/pivots/admissions/grades/assessments/ledgers/payments → users). Named in the seeder output.

## Results (executed 2026-09-29)

- `db:seed --class=StudentScatterSeeder` — **308 students created** (first run killed by a 15-min shell timeout at 120/308; the seeder's deficit-based design + resume-safe name-slot offset made the re-run continue cleanly with zero duplicates). Cleanup id ranges: users (195, 503], students (167, 475].
- `db:seed --class=GradesAssessmentsSeeder` (now additive) — **+6,558 grades, +26,232 assessments**; existing 3,099 grade values untouched (verified: exact totals 9,657; pre-scatter average 81.9 unchanged).

## Verification checklist

- [x] 30/30 sections = 15 Active enrollments (**450 total**, was 142)
- [x] 0 classes with zero students (was 81)
- [x] every Active enrollment has grades in all 3 terms — sole exception: enrollment 3 (Jose Reyes), the intentional demo "No grades" row
- [x] existing rows untouched (student 1, ledger 1 balance, grades 3,099 → 9,657 = +6,558 exactly)
- [x] smoke harness **219/219 PASS** on the grown DB; DB clean of SMOKE artifacts after final cleanup
- [x] docs + commit + push

**Final counts:** 503 users, 475 students, 450 active enrollments, 3,225 enrollment_subject pivots, 473 ledgers, 491 payments, 9,657 grades, 38,624 assessments.

---

# Follow-up — Full Linked Information for the 308 (Session 56, 2026-09-29)

**Ask:** make sure every one of the 450 active students is a fully-populated active account with all the various linked information the original roster has.

## Audit result (before this follow-up)

| Data | Old (≤167) | New (308) | Verdict |
|------|-----------|-----------|---------|
| users role 7, `status=active`, login-shaped (no `email_verified_at`, same as old) | 167/167 | **308/308** | ✅ already active accounts |
| profile, admission, enrollment, ledger, subject pivots, grades (3 terms), assessments | full | **308/308** | ✅ already filled |
| payments (paid/half/unpaid mix) | 152/167 | 286/308 | ✅ by-design unpaid mix |
| clinic logs | 120 students | **0** | ❌ fill |
| library transactions / visits | 202 / 37 rows | **0** | ❌ fill |
| graduation-fee assignments (G10/G12) | 19 | **0** | ❌ fill |
| admission requirement docs (3 types) | 15 admissions only | **0** | ❌ fill for all admissions |
| withdrawal requests | 15 (approved, event-based) | 0 | ➕ add 5 Pending for variety |

## Fill plan — new `StudentLinkedInfoSeeder` (bulk, idempotent)

1. **Library**: deterministic borrow/return history (1–2 per enrolled student, same formulas as `LibraryAndClinicSeeder`), library visits for every 4th student — built in memory, **chunk-inserted** (the old per-row seeder would take ~23 min over Supabase; bulk ≈ seconds).
2. **Clinic**: 1–2 visits per enrolled student except the `id % 7 === 3` never-visit group (20-complaint pool, nurse = role 6).
3. **Graduation fees**: every Active Grade 10 / Grade 12 enrollment without an assignment gets the fee (₱1,500 / ₱2,500, `paid` mix).
4. **Requirements**: all 473 admissions receive the 3 document rows (PSA Birth Certificate, Form 138, Good Moral — Verified/Under Review mix); existing 45 rows untouched, `file_content` stays null exactly like the originals.
5. **Withdrawals**: 5 `Pending` requests from new students (registrar queue variety; enrollment stays Active, student can still use the portal).

## Verification checklist (session 56)

- [x] fill run: **410 library txns + 77 library visits + 330 clinic logs + 71 grad-fee assignments + 1,374 requirement rows + 5 Pending withdrawals** (bulk, seconds) — coverage: new students clinic 264/308 (same never-visit rule as old 120/142), lib_txn 308/308, lib_visit 77, G10 30/30 + G12 60/60 grad fees, requirements 473/473 admissions (1,419 rows total), withdrawals 15 Approved + 5 Pending
- [x] **new student login over HTTP: PASS** — `aiden.aguilar1@agnusdei.edu.ph` → 302 → `/student/dashboard` 200 with name rendered (all 308 `status=active`, same login shape as old)
- [x] idempotency: re-run inserts **all zeros** (pending-withdrawal cap bug found & fixed during verification — candidates query now limited to `5 − existing`)
- [x] smoke harness **219/219**; existing rows untouched (first-15 requirement rows and all old linked data preserved)
- [x] docs + commit + push
