<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\FeeSchedule;
use App\Models\PromotionProposal;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentLedger;
use Illuminate\Support\Facades\DB;

/**
 * Executes Registrar-proposed, Principal-approved, Directress-signed-off
 * promotion proposals. Logic moved from the retired Admin\PromotionController
 * so no single IT account can run end-of-year promotion alone.
 */
class PromotionService
{
    public static function gradeRank(): array
    {
        return ['Kinder'=>0,'Grade 1'=>1,'Grade 2'=>2,'Grade 3'=>3,'Grade 4'=>4,'Grade 5'=>5,'Grade 6'=>6,'Grade 7'=>7,'Grade 8'=>8,'Grade 9'=>9,'Grade 10'=>10,'Grade 11'=>11,'Grade 12'=>12];
    }

    /**
     * Compute year-end qualification for one enrollment.
     * Final per subject = average of term grades per class.
     */
    public static function qualify(Enrollment $enrollment, int $passingGrade): array
    {
        $grades = $enrollment->grades ?? collect();
        $grouped = $grades->groupBy('class_id');
        $finals = $grouped->map(fn($g) => round($g->avg('final_grade'), 2));
        $avg = $finals->isNotEmpty() ? round($finals->avg(), 2) : null;
        $failCount = $finals->filter(fn($f) => $f < $passingGrade)->count();

        return [
            'finals' => $finals,
            'avg' => $avg,
            'failCount' => $failCount,
            'subjectCount' => $finals->count(),
            'qualified' => $avg !== null && $avg >= $passingGrade && $failCount === 0,
        ];
    }

    /**
     * Execute one Directress-approved proposal. Throws on any rule violation
     * so the caller can report per-student errors without partial writes.
     */
    public static function execute(PromotionProposal $proposal): void
    {
        $enrollment = $proposal->enrollment()->with('student.ledger', 'section')->first();

        if (!$enrollment || $enrollment->status !== 'Active') {
            throw new \Exception("Enrollment #{$proposal->enrollment_id} not found or not active.");
        }

        if (in_array($proposal->action, ['promote', 'retain'], true)) {
            $existing = Enrollment::where('student_id', $enrollment->student_id)
                ->where('school_year', $proposal->school_year)
                ->where('status', 'Active')
                ->exists();
            if ($existing) {
                throw new \Exception("{$enrollment->student->first_name} {$enrollment->student->last_name}: Already has an active enrollment for {$proposal->school_year}.");
            }
        }

        if (in_array($proposal->action, ['transfer', 'dropped'], true) && empty(trim((string) $proposal->reason))) {
            throw new \Exception("{$enrollment->student->first_name} {$enrollment->student->last_name}: Reason required for " . ($proposal->action === 'transfer' ? 'Transfer' : 'Dropped Out') . '.');
        }

        match ($proposal->action) {
            'promote' => self::promote($enrollment, $proposal->school_year),
            'retain' => self::retain($enrollment, $proposal->school_year),
            'graduate' => self::graduate($enrollment),
            'transfer' => self::transfer($enrollment, $proposal->reason),
            'dropped' => self::dropped($enrollment, $proposal->reason),
            default => throw new \Exception("Unknown promotion action: {$proposal->action}."),
        };
    }

    private static function promote(Enrollment $enrollment, string $newSchoolYear)
    {
        $currentGrade = $enrollment->section?->grade_level;
        $nextGrade = self::getNextGradeLevel($currentGrade);

        if (!$nextGrade) {
            throw new \Exception("{$currentGrade} cannot be promoted (set to Graduate instead).");
        }

        $section = Section::where('grade_level', $nextGrade)->where('is_active', true)->first();
        if (!$section) {
            throw new \Exception("No active section found for {$nextGrade}.");
        }

        $student = $enrollment->student;

        $newEnrollment = Enrollment::create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'school_year' => $newSchoolYear,
            'strand' => $enrollment->strand,
            'status' => 'Active',
        ]);

        $newClasses = Classes::where('grade_level', $nextGrade)
            ->where('school_year', $newSchoolYear)
            ->where('section', $section->section_name)
            ->where('status', 'active')
            ->get();

        if ($newClasses->isNotEmpty()) {
            $newEnrollment->subjects()->attach($newClasses->pluck('id'));
        }

        self::carryFees($student, $nextGrade, $newSchoolYear);

        $enrollment->status = 'Promoted';
        $enrollment->promoted_to_enrollment_id = $newEnrollment->id;
        $enrollment->save();

        log_activity($enrollment, 'Promoted', "Promoted {$student->first_name} {$student->last_name} from {$currentGrade} to {$nextGrade}");
    }

    private static function retain(Enrollment $enrollment, string $newSchoolYear)
    {
        $currentGrade = $enrollment->section?->grade_level;

        $section = Section::find($enrollment->section_id);
        if (!$section) {
            throw new \Exception("No active section found for {$currentGrade}.");
        }

        $student = $enrollment->student;

        $newEnrollment = Enrollment::create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'school_year' => $newSchoolYear,
            'strand' => $enrollment->strand,
            'status' => 'Active',
        ]);

        $newClasses = Classes::where('grade_level', $currentGrade)
            ->where('school_year', $newSchoolYear)
            ->where('section', $section->section_name)
            ->where('status', 'active')
            ->get();

        if ($newClasses->isNotEmpty()) {
            $newEnrollment->subjects()->attach($newClasses->pluck('id'));
        }

        self::carryFees($student, $currentGrade, $newSchoolYear);

        $enrollment->status = 'Retained';
        $enrollment->promoted_to_enrollment_id = $newEnrollment->id;
        $enrollment->save();

        log_activity($enrollment, 'Retained', "Retained {$student->first_name} {$student->last_name} in {$currentGrade}");
    }

    private static function graduate(Enrollment $enrollment)
    {
        $enrollment->status = 'Graduated';
        $enrollment->save();

        $enrollment->student->status = 'graduated';
        $enrollment->student->save();

        log_activity($enrollment, 'Graduated', "Graduated {$enrollment->student->first_name} {$enrollment->student->last_name}");
    }

    private static function transfer(Enrollment $enrollment, ?string $reason = null)
    {
        $enrollment->status = 'Transferred';
        $enrollment->save();

        $enrollment->student->status = 'archived';
        $enrollment->student->archive_action = 'transferred';
        $enrollment->student->archive_reason = $reason ?? 'Transferred out';
        $enrollment->student->archived_at = now();
        $enrollment->student->save();

        log_activity($enrollment, 'Transferred', "Transferred out {$enrollment->student->first_name} {$enrollment->student->last_name}" . ($reason ? " — {$reason}" : ""));
    }

    private static function dropped(Enrollment $enrollment, ?string $reason = null)
    {
        $enrollment->status = 'Dropped';
        $enrollment->save();

        $enrollment->student->status = 'archived';
        $enrollment->student->archive_action = 'dropped';
        $enrollment->student->archive_reason = $reason ?? 'Dropped out';
        $enrollment->student->archived_at = now();
        $enrollment->student->save();

        log_activity($enrollment, 'Dropped', "Dropped {$enrollment->student->first_name} {$enrollment->student->last_name}" . ($reason ? " — {$reason}" : ""));
    }

    private static function carryFees(Student $student, string $gradeLevel, string $schoolYear)
    {
        $ledger = $student->ledger;

        if (!$ledger) {
            return;
        }

        $oldBalance = $ledger->balance;

        $termFees = FeeSchedule::where('grade_level', $gradeLevel)
            ->where('school_year', $schoolYear)
            ->get();

        $newFeesTotal = $termFees->sum(fn($f) => $f->tuition_fee + $f->misc_fee);

        if ($newFeesTotal > 0 || $oldBalance > 0) {
            $ledger->carried_over_balance = $oldBalance;
            $ledger->total_assessed += $newFeesTotal;
            $ledger->balance += $newFeesTotal;
            $ledger->clearance_status = 'Uncleared';
            $ledger->it_confirmed_at = null;
            $ledger->save();
            LedgerService::refreshClearance($ledger);
        }
    }

    private static function getNextGradeLevel(?string $current): ?string
    {
        $grades = [
            'Kinder'  => 'Grade 1',
            'Grade 1' => 'Grade 2',
            'Grade 2' => 'Grade 3',
            'Grade 3' => 'Grade 4',
            'Grade 4' => 'Grade 5',
            'Grade 5' => 'Grade 6',
            'Grade 6' => 'Grade 7',
            'Grade 7' => 'Grade 8',
            'Grade 8' => 'Grade 9',
            'Grade 9' => 'Grade 10',
            'Grade 10' => 'Grade 11',
            'Grade 11' => 'Grade 12',
            'Grade 12' => null,
        ];

        return $grades[$current] ?? null;
    }
}
