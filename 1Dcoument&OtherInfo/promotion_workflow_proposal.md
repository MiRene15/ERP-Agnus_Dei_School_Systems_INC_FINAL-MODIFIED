# Promotion Workflow Proposal — Cleaner Status Lifecycle

> **Status: PROPOSAL — awaiting go signal. No code has been changed.**
> Covers: Promote, Retain, Graduate, Transfer (Transfer Out), and Dropout.

## 1. Problem Today
- Current `Enrollments.status` mixes lifecycle states: `Active`, `Promoted`, `Retained`, `Graduated`, `Withdrawn`. The `students.status` mirrors some of these (`enrolled`, `archived`, `graduated`).
- Transfer and Dropout both map to `Withdrawn` / `archived` — no distinction in reports or audit logs (`Transferred` vs actual intent).
- No single timeline view: you cannot tell if a student was retained due to failing grades vs transferred vs dropped out without reading separate logs.
- Grades qualification is not shown on the promotion table today (being added as quick fix: GWA + Qualified/Not qualified badge — see `promotion-index-results.blade.php`).

## 2. Proposed Unified Lifecycle

### 2.1 Statuses (enrollments.status)
Keep existing but **add `Dropped Out`** and **split `Withdrawn`**:

| Status | Meaning | student.status side-effect |
|---|---|---|
| `Active` | Currently enrolled this SY | `enrolled` |
| `Promoted` | Moved to next grade in `school_year+1` | stays `enrolled` |
| `Retained` | Repeated same grade in `school_year+1` | stays `enrolled` |
| `Graduated` | Finished Grade 6 / 10 / 12 terminal | `graduated` |
| `Transferred` | Transferred to another school (with transfer docs) | `archived` + `archive_action='transferred'` |
| `Dropped Out` | Voluntary/involuntary dropout (no transfer docs) | `archived` + `archive_action='dropped'` |
| `Withdrawn` | Mid-year withdrawal (existing registrar withdrawal flow) | `archived` + `archive_action='withdrawn'` |

> **No promotion for dropped/transferred students** — their old enrollment stays as that status; a new enrollment is **not** created for `school_year+1` (unlike promote/retain).

### 2.2 Actions (Admin → Promotion page + Registrar → Withdrawal page)
- **Promotion page** (`admin/promotion`): Promote / Retain / Graduate / Transfer / Dropout — **all 5** in one table, with GWA + Qualified badge already added.
- **Mid-year withdrawal** stays at `registrar/withdrawals` (student-initiated, registrar approves) → results in `Withdrawn`.
- **Transfer-Out vs Dropout at year-end** are admin decisions (different `archive_reason` required for each).

### 2.3 Requested Workflow (Step-by-Step)
```
Registrar verifies grades → Admin opens Promotion (grouped by grade)
  → Each row shows: Student, Section, GWA, Qualified?, Balance, Action dropdown
  → Default selection: Qualified → Promote, Not qualified → Retain, Grade 12 → Graduate
  → Admin can override per row (e.g., Transfer / Dropout even if qualified)
  → Select New School Year → Process All → DB transaction per student
       - promote/retain: create new Enrollment (Active) + attach classes + carryFees()
       - graduate: enrollment Graduated, student graduated
       - transfer/dropout/withdrawn: enrollment Transferred/Dropped Out/Withdrawn, student archived
       - log_activity() with distinct event per action
```

### 2.4 Guards & Validation
- Duplicate prevention: `promote`/`retain` blocked if student already has `Active` enrollment for `school_year`.
- `promote` blocked for Grade 12 (force Graduate).
- `graduate` only for Grade 6 / 10 / 12 (existing check kept).
- Transfer / Dropout require `archive_reason` (new field on promotion form — text input per row, revealed when that action is selected).
- Balance warning (not blocker): show red balance but still allow; business rule: cashier clearance is separate from academic promotion.

### 2.5 DB Changes (only if approved)
- `enrollments.status` CHECK → add `Transferred`, `Dropped Out` (or `DroppedOut`) if not already allowed (currently free-form, but add constraint).
- `students` : add `archive_action` ENUM (`transferred`, `dropped`, `withdrawn`, `graduated`) + reuse `archive_reason`, `archived_at` (already exist per `Student Profile Module`).
- Optional `enrollment_id` → `new_enrollment_id` already via `promoted_to_enrollment_id` — keep, rename conceptually to `next_enrollment_id` in docs only.

### 2.6 UI Changes (only if approved)
- `promotion-index-results.blade.php`: add 5th action option `Dropout`; when Transfer/Dropout selected, show `Reason` text input (500 chars) inline.
- Add filter: `All / Qualified / Not qualified / No grades / With balance` to quickly triage.
- Add `View Grades` link per row → modal with per-subject final_grade.
- Reports: Export → add `status` + `archive_action` columns.

### 2.7 Migration Path for Existing Data
- Existing `Withdrawn` rows that were Transfer Out remain as `Withdrawn` + `archive_action='transferred'` (backfill via one-time script).
- No data loss.

## 3. What to Decide (Go / No-Go)
- [ ] Approve adding `Dropped Out` + distinct `Transferred` vs `Withdrawn`
- [ ] Approve `archive_action` + `archive_reason` per year-end action
- [ ] Approve GWA qualification rule stays: `avg >=75 && 0 failing` (or change to school's honors rule: no grade <87 for promote?)
- [ ] Approve UI filter + reason field

— End proposal —
