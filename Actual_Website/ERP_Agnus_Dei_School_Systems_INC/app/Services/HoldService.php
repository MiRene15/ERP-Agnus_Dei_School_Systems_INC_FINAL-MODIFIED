<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\ClinicLog;
use App\Models\LibraryTransaction;
use App\Models\Student;
use App\Models\StudentLedger;
use Illuminate\Support\Collection;

/**
 * Holds engine (role reform Phase 3).
 *
 * Three automatic hold sources block report cards, re-enrollment and
 * promotion until cleared. Holds are computed, never stored, so clearing
 * the underlying cause (return the book, close the case, pay the balance)
 * lifts the hold immediately with no extra step — history lives in the
 * underlying records' activity logs (a mistaken hold lifts the same way
 * with its raise/lift trail kept).
 *
 *   library — any overdue unreturned loan
 *   clinic  — any open clinic case (pending follow-up / referral)
 *   finance — any outstanding ledger balance
 *
 * Spec enrollment-payment-gating-calendar-holds.md §6: re-enrollment
 * holds are VISIBLE iff a new school year is announced AND the returnee
 * has dues. New applicants never see holds; Grade 12 / Graduated students
 * never get re-enrollment holds (completion shows instead); mid-year
 * transferees are covered for their own dues only (holds are per-student).
 * Wording is status-only: "Settle <what> with <office> to re-enroll".
 */
class HoldService
{
    /**
     * Raw dues truth for staff enforcement (report cards, approvals).
     * Holds for one student. Each hold: ['source', 'reason', 'action'].
     */
    public static function forStudent(Student $student): array
    {
        return self::forStudentIds(collect([$student->id]))[$student->id] ?? [];
    }

    /**
     * Batch version — 3 queries total regardless of roster size.
     * Returns [student_id => holds[]].
     */
    public static function forStudentIds(Collection $studentIds): array
    {
        $ids = $studentIds->filter()->unique()->values();
        if ($ids->isEmpty()) return [];

        $out = [];
        foreach ($ids as $id) $out[$id] = [];

        // Library: overdue unreturned loans.
        // Spec cashier-fee-rules.md: Cashier's receipt wins — a due settled
        // at Cashier (paid_payment_id set) lifts the hold on confirmation.
        $overdue = LibraryTransaction::whereIn('student_id', $ids)
            ->where('status', 'Borrowed')
            ->whereNotNull('return_date')
            ->whereDate('return_date', '<', now()->toDateString())
            ->whereNull('returned_at')
            ->whereNull('paid_payment_id')
            ->with('book')
            ->get();
        foreach ($overdue->groupBy('student_id') as $sid => $loans) {
            foreach ($loans as $loan) {
                $title = $loan->book->title ?? $loan->book_title ?? 'a library book';
                $days = $loan->daysOverdue();
                $out[$sid][] = [
                    'source' => 'library',
                    'reason' => "Overdue book: “{$title}” ({$days} day(s) overdue, due {$loan->return_date->format('M d, Y')})",
                    'action' => 'Return the book to the library (or settle the fee with the Cashier).',
                ];
            }
        }

        // Clinic: open cases.
        $openCases = ClinicLog::whereIn('student_id', $ids)
            ->where('is_open', true)
            ->orderByDesc('visit_date')
            ->get();
        foreach ($openCases->groupBy('student_id') as $sid => $cases) {
            foreach ($cases as $case) {
                $when = $case->visit_date ? \Carbon\Carbon::parse($case->visit_date)->format('M d, Y') : 'recent visit';
                $what = $case->complaint ?: ($case->symptoms ?: 'a clinic case');
                $out[$sid][] = [
                    'source' => 'clinic',
                    'reason' => "Open clinic case from {$when}: {$what}",
                    'action' => 'See the nurse to close the case.',
                ];
            }
        }

        // Finance: outstanding balance. Only ledgers count — no ledger, no hold.
        // Status-only wording per spec §4.3 (never a peso amount here).
        $ledgers = StudentLedger::whereIn('student_id', $ids)
            ->where('balance', '>', 0)
            ->get();
        foreach ($ledgers as $ledger) {
            $out[$ledger->student_id][] = [
                'source' => 'finance',
                'reason' => 'Unsettled account balance',
                'action' => 'Settle your balance with the Cashier\'s Office to re-enroll.',
            ];
        }

        return $out;
    }

    /**
     * Spec fee-itemized-manager.md: staff approval path. New applicants
     * enroll on downpayment receipt — the normal current-year installment
     * remainder (months + 4 named lines still unpaid) must not block
     * Grant Full Approval. Prior-year dues, library overdue, and open
     * clinic cases still block everyone, and returnees with last-year
     * dues still need clearance plus downpayment.
     *
     * @return array<int, array{source: string, reason: string, action: string}>
     */
    public static function forApproval(Student $student, ?Admission $admission = null): array
    {
        $holds = self::forStudent($student);

        if ($admission === null || empty($admission->downpayment_receipt)) {
            return $holds;
        }

        // Returnees carry last-year dues into approval — clearance enforced.
        if (self::isReturnee($student)) {
            return $holds;
        }

        $year = (string) ($admission->school_year ?? '');
        if ($year === '') {
            return $holds;
        }

        $current = StudentLedger::where('student_id', $student->id)
            ->where('school_year', $year)
            ->where('balance', '>', 0)
            ->get();

        if ($current->isEmpty()) {
            return $holds;
        }

        $isInstallment = $current->contains(
            fn (StudentLedger $ledger): bool => ($ledger->assessment_path ?? $ledger->payment_plan) === 'installment'
        );

        if (! $isInstallment) {
            return $holds;
        }

        $hasOtherYearDues = StudentLedger::where('student_id', $student->id)
            ->where('school_year', '!=', $year)
            ->where('balance', '>', 0)
            ->exists();

        $kept = array_values(array_filter(
            $holds,
            fn (array $hold): bool => ($hold['source'] ?? '') !== 'finance'
        ));

        if ($hasOtherYearDues) {
            $kept[] = [
                'source' => 'finance',
                'reason' => 'Unsettled account balance',
                'action' => 'Settle your balance with the Cashier\'s Office to re-enroll.',
            ];
        }

        return $kept;
    }

    /**
     * Student-facing re-enrollment holds: gated on announced new year AND
     * returnee with dues. Returns [] for new applicants, settled returnees,
     * final-level graduates, and everyone before announcement.
     */
    public static function visibleForStudent(Student $student): array
    {
        if (! \App\Services\SchoolYearService::isNewYearAnnounced()) {
            return [];
        }

        if (! self::isReturnee($student)) {
            return [];
        }

        if (self::isFinalGraduate($student)) {
            return [];
        }

        return self::forStudent($student);
    }

    /**
     * A returnee has a student number plus at least one Active enrollment,
     * or a Graduated enrollment as a continuer (Kinder/Grade 6/Grade 10
     * graduates re-enrolling to the next level — spec
     * promotion-section-capacity §6 amendment). Terminal Grade 12 graduates
     * are returnees here too, but visibleForStudent still excludes them via
     * isFinalGraduate. First-time applicants have neither.
     */
    public static function isReturnee(Student $student): bool
    {
        if (empty($student->student_number)) {
            return false;
        }

        return $student->enrollments()->whereIn('status', ['Active', 'Graduated'])->exists();
    }

    /**
     * Terminal completers show completion instead. Only Grade 12 counts as
     * final — Kinder/Grade 6/Grade 10 graduates are continuers, not finals.
     */
    public static function isFinalGraduate(Student $student): bool
    {
        $graduatedGrades = $student->enrollments()
            ->where('status', 'Graduated')
            ->with('section')
            ->get()
            ->map(fn ($e) => $e->section->grade_level ?? null);
        if ($graduatedGrades->contains('Grade 12')) {
            return true;
        }
        if ($graduatedGrades->isNotEmpty()) {
            return false;
        }

        $latestGrade = $student->enrollments()
            ->where('status', 'Active')
            ->latest()
            ->with('section')
            ->get()
            ->map(fn ($e) => $e->section->grade_level ?? null)
            ->first();

        return $latestGrade === 'Grade 12';
    }

    /**
     * Banner state for the student dashboard.
     *
     * @return array{state: string, label: string}
     */
    public static function bannerForStudent(Student $student, ?string $schoolYear = null): array
    {
        $year = $schoolYear ?? active_school_year();

        if (self::isFinalGraduate($student)) {
            return ['state' => 'complete', 'label' => 'Completed — congratulations, graduate!'];
        }

        $holds = self::visibleForStudent($student);

        if ($holds === []) {
            return ['state' => 'clear', 'label' => "You're clear for {$year}"];
        }

        $first = $holds[0];
        $office = match ($first['source']) {
            'library' => 'the Library',
            'clinic' => 'the Clinic',
            default => 'the Cashier\'s Office',
        };
        $what = match ($first['source']) {
            'library' => 'overdue book',
            'clinic' => 'clinic case',
            default => 'account balance',
        };

        return ['state' => 'hold', 'label' => "Settle {$what} with {$office} to re-enroll"];
    }

    public static function hasHolds(Student $student): bool
    {
        return !empty(self::forStudent($student));
    }

    /**
     * One-line summary for flash messages, e.g.
     * "Blocked by 2 hold(s): [Library] ..., [Finance] ...".
     */
    public static function blockingMessage(array $holds): string
    {
        $parts = array_map(fn($h) => '[' . ucfirst($h['source']) . '] ' . $h['reason'], $holds);
        return 'Blocked by ' . count($holds) . ' hold(s): ' . implode(' | ', $parts);
    }
}
