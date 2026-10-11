<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Admission;
use App\Models\ClinicLog;
use App\Models\Enrollment;
use App\Models\LibraryTransaction;
use App\Models\LibraryVisit;
use App\Models\Student;
use App\Models\StudentGraduationFee;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

/**
 * Duplicate student survivor-pick (spec: promotion-section-capacity §6).
 * Staff picks the survivor; enrollments (grades/attendance/assessments ride
 * along via enrollment_id), admissions, clinic, library, visits, grad fees
 * and withdrawals follow the survivor. Ledger: moved only when the survivor
 * has none (payments ride via ledger_id); when both hold ledgers the
 * survivor's wins and the loser's balance is logged for Cashier review —
 * money is never auto-merged. The loser is archived, never hard-deleted.
 */
class StudentMergeService
{
    /**
     * @return array{merged: bool, message: string}
     */
    public static function merge(int $survivorId, int $loserId, string $registrarName): array
    {
        if ($survivorId === $loserId) {
            return ['merged' => false, 'message' => 'Pick two different records — survivor and loser cannot be the same.'];
        }

        return DB::transaction(function () use ($survivorId, $loserId, $registrarName) {
            $survivor = Student::find($survivorId);
            $loser = Student::find($loserId);
            if ($survivor === null || $loser === null) {
                return ['merged' => false, 'message' => 'One of the records no longer exists.'];
            }
            if ($loser->status === 'archived') {
                return ['merged' => false, 'message' => 'That record is already archived — nothing to merge.'];
            }

            Enrollment::where('student_id', $loser->id)->update(['student_id' => $survivor->id]);
            Admission::where('student_id', $loser->id)->update(['student_id' => $survivor->id]);
            ClinicLog::where('student_id', $loser->id)->update(['student_id' => $survivor->id]);
            LibraryTransaction::where('student_id', $loser->id)->update(['student_id' => $survivor->id]);
            LibraryVisit::where('student_id', $loser->id)->update(['student_id' => $survivor->id]);
            StudentGraduationFee::where('student_id', $loser->id)->update(['student_id' => $survivor->id]);
            Withdrawal::where('student_id', $loser->id)->update(['student_id' => $survivor->id]);

            $survivorLedger = $survivor->ledger;
            $loserLedger = $loser->ledger;
            $moneyNote = '';
            if ($survivorLedger === null && $loserLedger !== null) {
                $loserLedger->student_id = $survivor->id;
                $loserLedger->save();
            } elseif ($survivorLedger !== null && $loserLedger !== null) {
                $moneyNote = " Loser's ledger balance ₱" . number_format((float) $loserLedger->balance, 2) . ' kept for Cashier review — ledgers were not auto-merged.';
            }

            $loser->status = 'archived';
            $loser->archive_action = 'duplicate';
            $loser->archive_reason = "Duplicate merged into {$survivor->student_number} ({$survivor->first_name} {$survivor->last_name}) by {$registrarName}.";
            $loser->archived_at = now();
            $loser->save();

            log_activity($survivor, 'Duplicate Merged', "{$registrarName} merged duplicate {$loser->first_name} {$loser->last_name} ({$loser->student_number}) into survivor {$survivor->student_number}.{$moneyNote}");
            log_activity($loser, 'Archived', "Archived as duplicate of {$survivor->student_number} by {$registrarName}.");

            return ['merged' => true, 'message' => "Merged into {$survivor->first_name} {$survivor->last_name} ({$survivor->student_number}) — grades, attendance and history follow the survivor; loser archived, never deleted.{$moneyNote}"];
        });
    }
}
