<?php

namespace App\Services;

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
 * lifts the hold immediately with no extra step.
 *
 *   library — any overdue unreturned loan
 *   clinic  — any open clinic case (pending follow-up / referral)
 *   finance — any outstanding ledger balance
 */
class HoldService
{
    /**
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
        $overdue = LibraryTransaction::whereIn('student_id', $ids)
            ->where('status', 'Borrowed')
            ->whereNotNull('return_date')
            ->whereDate('return_date', '<', now()->toDateString())
            ->whereNull('returned_at')
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
        $ledgers = StudentLedger::whereIn('student_id', $ids)
            ->where('balance', '>', 0)
            ->get();
        foreach ($ledgers as $ledger) {
            $out[$ledger->student_id][] = [
                'source' => 'finance',
                'reason' => 'Outstanding balance ₱' . number_format($ledger->balance, 2),
                'action' => 'Settle your account at the Cashier’s Office.',
            ];
        }

        return $out;
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
