<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\FeeSchedule;
use App\Models\GraduationFee;
use App\Models\StudentGraduationFee;
use App\Models\StudentLedger;
use Illuminate\Http\Request;

/**
 * Bulk fee assignment from enrollment (role reform Phase 4c).
 * Registrar records: one click assigns tuition ledgers + graduation fees
 * to every eligible enrollment — no more one-by-one assignment.
 */
class FeeAssignmentController extends Controller
{
    public function index()
    {
        $schoolYear = active_school_year();

        $missingLedgers = Enrollment::with('student', 'section')
            ->where('status', 'Active')
            ->where('school_year', $schoolYear)
            ->whereDoesntHave('student.ledger')
            ->orderBy('id')
            ->get();

        $gradFees = GraduationFee::orderByDesc('school_year')->orderBy('grade_level')->get()->map(function ($fee) {
            $eligible = Enrollment::where('status', 'Active')
                ->whereHas('section', fn($q) => $fee->grade_level ? $q->where('grade_level', $fee->grade_level) : $q)
                ->when($fee->school_year, fn($q) => $q->where('school_year', $fee->school_year));
            $assignedIds = StudentGraduationFee::where('graduation_fee_id', $fee->id)->pluck('student_id')->all();
            $fee->eligible_count = (clone $eligible)->count();
            $fee->missing_count = (clone $eligible)->whereNotIn('student_id', $assignedIds)->count();
            return $fee;
        });

        return view('portal.registrar.fee-assignment.index', compact('missingLedgers', 'gradFees', 'schoolYear'));
    }

    /**
     * Create tuition ledgers for every Active enrollment still missing one.
     */
    public function assignLedgers()
    {
        $schoolYear = active_school_year();

        $enrollments = Enrollment::with('section')
            ->where('status', 'Active')
            ->where('school_year', $schoolYear)
            ->whereDoesntHave('student.ledger')
            ->get();

        $created = 0;
        foreach ($enrollments as $enrollment) {
            $grade = $enrollment->section?->grade_level;
            if (!$grade) continue;

            $total = FeeSchedule::where('grade_level', $grade)
                ->where('school_year', $enrollment->school_year)
                ->get()
                ->sum(fn($f) => $f->tuition_fee + $f->misc_fee);

            StudentLedger::create([
                'student_id' => $enrollment->student_id,
                'payment_plan' => 'installment',
                'total_assessed' => $total,
                'discount_type' => null,
                'discount_applied' => 0,
                'total_paid' => 0,
                'balance' => max(0, $total),
                'clearance_status' => 'Uncleared',
            ]);
            $created++;
        }

        log_activity(new StudentLedger, 'Ledgers Bulk Assigned', auth()->user()->name . " (Registrar) bulk-assigned {$created} tuition ledger(s) from enrollment for SY {$schoolYear}.");

        return back()->with('success', "Assigned {$created} tuition ledger(s) from enrollment.");
    }

    /**
     * Assign one graduation fee to every eligible enrollment missing it.
     */
    public function assignGradFees(Request $request)
    {
        $data = $request->validate([
            'graduation_fee_id' => 'required|exists:graduation_fees,id',
        ]);

        $fee = GraduationFee::findOrFail($data['graduation_fee_id']);
        $totalPerStudent = $fee->graduation_fee + $fee->other_fees;

        $assignedIds = StudentGraduationFee::where('graduation_fee_id', $fee->id)->pluck('student_id')->all();

        $enrollments = Enrollment::where('status', 'Active')
            ->whereHas('section', fn($q) => $fee->grade_level ? $q->where('grade_level', $fee->grade_level) : $q)
            ->when($fee->school_year, fn($q) => $q->where('school_year', $fee->school_year))
            ->whereNotIn('student_id', $assignedIds)
            ->get();

        $created = 0;
        foreach ($enrollments as $enrollment) {
            StudentGraduationFee::create([
                'student_id' => $enrollment->student_id,
                'enrollment_id' => $enrollment->id,
                'graduation_fee_id' => $fee->id,
                'amount' => $totalPerStudent,
            ]);
            $created++;
        }

        log_activity($fee, 'Graduation Fee Bulk Assigned', auth()->user()->name . " (Registrar) bulk-assigned graduation fee to {$created} student(s).");

        return back()->with('success', "Assigned graduation fee to {$created} student(s).");
    }
}
