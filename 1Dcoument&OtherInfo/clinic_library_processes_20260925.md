# Clinic Information + Library Processes — Roster-Wide Seed Data — 2026-09-25

> **Request:** "Could you include clinic information and library processes?" (alongside the 165-student roster expansion)
> **Status:** ✅ Executed — MDs written first per instruction

---

## 1. Before → After

| Data | Before | After |
|---|---|---|
| Library transactions (borrow/return) | 35 (random students, re-runs duplicate) | **~180** — deterministic, up to 2 per enrolled student, spans 60 days |
| Clinic logs | 20 (random, last 30 days) | **~140** — every enrolled student (some with 2 visits), spans 60 days |
| Library visits (clock in/out) | 12, lived in `AuditLogsAndExtrasSeeder` | **~35**, moved into `LibraryAndClinicSeeder` (correct home), spans 5 days |

## 2. Changes — `database/seeders/LibraryAndClinicSeeder.php`

### Library processes (rewritten, fully deterministic → idempotent)
- Iterates **all enrolled students** (ordered by id) instead of `->random()` — every student has circulation history; re-runs hit the same `updateOrCreate` keys (no duplicates)
- Book pick derived from student id (`($id * 7 + $n * 11) % bookCount`), borrow date from `($id * 5) % 60` days ago → date-range filters show real spread
- Status mix preserved: ~1/5 currently **Borrowed** (1/4 of those **overdue**), rest **Returned** with on-time/early/late variance
- **Late fees actually seed now**: `lateDays = max(0, returnDate->diffInDays(actualReturnDate))` (version-safe for Carbon 3 in Laravel 12; old formula always yielded 0 under absolute diffs) → `total_fees = lateDays × ₱5` feeds the Library report's fine totals

### Clinic information (rewritten, deterministic)
- ~140 logs: every enrolled student ≥1 visit, students with `$id % 4 === 1` get a 2nd, `$id % 7 === 3` skip → unique-patient + repeat-visit mix for the Clinic tab's "Unique Patients" metric
- Visit dates spread over 60 days (`($id * 2) % 60`) → From/To filters meaningful
- Complaints cycle the existing 20-condition library (fever, asthma, abrasions, referrals to Dr. Reyes / School Dentist / ENT…) → Top Symptoms / Top Diagnoses / Referrals-Out all populated
- Key = `(student_id, incident_date, complaint)` → idempotent

### Library visits (moved + expanded)
- `seedLibraryVisits()` moved from `AuditLogsAndExtrasSeeder` → `LibraryAndClinicSeeder` (it's a library process); students with `$id % 4 === 0` clock in across the last 5 days, ~3/4 have `time_out` → "Library Clock In/Out" audit events + librarian visit log populated

## 3. Files
| File | Change |
|---|---|
| `database/seeders/LibraryAndClinicSeeder.php` | deterministic transactions (~180) + clinic logs (~140) + `seedLibraryVisits()` (~35) |
| `database/seeders/AuditLogsAndExtrasSeeder.php` | `seedLibraryVisits()` removed (moved) |
| `seeder_gaps_20260925.md` | superseded note: library visits now seeded in `LibraryAndClinicSeeder` |

## 4. Verification
- `php -l` both seeders.
- ✅ **Executed against live Supabase DB** (2026-09-25): clinic **157 logs / 120 unique patients**, library **202 transactions** (41 borrowed incl. overdue, ₱480 late fees), **37 visits**, 35 books. `AuditLogsAndExtrasSeeder` also live-seeded: activity_log 103 → 138, 2 graduation fees + 19 assignments, 15 withdrawals, 3 inquiries, 45 requirements.
