# Double Subjects & Fee Breakdown Fix

## 1. Double Subjects in COR & Grading

**Root cause:** `enrollment_subject` pivot holds `class_id` rows per term. Same subject (e.g., Math) has 3 `Classes` rows (one per term: 1st/2nd/3rd) with same `subject_id` but different `term`. `Enrollment->subjects` (via pivot) loads all 3, so COR lists "Math" 3 times, and grading lists duplicate subject columns.

**Current code:**
- `StudentController@cor:50` — `with('subjects')` loads all terms
- `TeacherController@computedGrades` / `gradeAssessment` — `Classes::where('grade_level', ...)` without term filter

**Fix (per request: filter by term):**
- Add `current_term` setting (already exists: `SettingsSeeder` has `current_term`, default `1st Term`). Use `Setting::getValue('current_term', '1st Term')` as filter.
- In `StudentController@cor` and `StudentController@schedule`, filter `subjects` relation: `->where('term', $currentTerm)` or if term is null/empty, show all but dedup by `subject_id`.
- In `TeacherController` grading queries, filter `Classes` by `term = $selectedGradingPeriod` mapping? But `Classes` term is like "1st Term" / "2nd Term" / "3rd Term" or null. For grading, the teacher selects `grading_period` (1st/2nd/3rd Term) and should see only classes for that term. So filter `Classes` where `term = $selectedPeriod`.
- For COR, filter by current term; for historical view, allow term switcher.

## 2. Fee Breakdown

**Current:**
- `StudentController@cor` already shows tuition/misc/total per term with discount/total paid/balance in `cor.blade.php:108`.
- `StudentController@ledger` (`student/ledger` + `ledger-results`) shows summary but not per-fee-type breakdown.
- `CashierController@studentFinancial` shows summary but not per-term/per-fee breakdown.

**Required (per request):**
- **Student Statement of Account** (`portal/student/ledger`): Show table: Term | Tuition | Misc | Discount | Paid (per term pro-rated) | Balance per term + totals.
- **Cashier Financial Overview** (`portal/cashier/financial/{student}`): Same breakdown per student, plus payment history.
- **COR:** Already has breakdown, but ensure it matches the other two (use same helper).

**Implementation:**
- Helper `feeBreakdown($grade_level, $school_year, $ledger)` returns array per term: `['term'=>..., 'tuition'=>..., 'misc'=>..., 'total'=>..., 'discount_share'=>..., 'paid_share'=>..., 'balance'=>...]`
- Discount and total_paid are ledger-level; distribute pro-rata by term total weight (or show ledger-level totals with per-term assessed).
- Keep `FeeSchedule` as source of truth; no schema change.

**Status:** MDS updated before execution, code to follow (no push until approved).
