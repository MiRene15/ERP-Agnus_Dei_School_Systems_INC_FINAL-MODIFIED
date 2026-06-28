<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\FeeSchedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentLedger;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index()
    {
        $enrollments = Enrollment::with('student', 'section')
            ->where('status', 'Active')
            ->where('school_year', active_school_year())
            ->orderBy('school_year', 'desc')
            ->orderBy(Student::selectRaw("CONCAT(first_name, ' ', last_name)")
                ->whereColumn('students.id', 'enrollments.student_id')
            )
            ->get()
            ->groupBy(fn($e) => $e->section?->grade_level ?? 'Unknown');

        $actions = [
            'promote' => 'Promote to next grade',
            'retain' => 'Retain in same grade',
            'graduate' => 'Graduate',
            'transfer' => 'Transfer Out',
        ];

        $schoolYears = Enrollment::distinct()->orderBy('school_year', 'desc')->pluck('school_year');

        return view('portal.admin.promotion.index', compact('enrollments', 'actions', 'schoolYears'));
    }

    public function process(Request $request)
    {
        $data = $request->validate([
            'actions' => 'required|array',
            'actions.*' => 'required|in:promote,retain,graduate,transfer',
            'school_year' => 'required|string|max:20',
        ]);

        $results = ['promoted' => 0, 'retained' => 0, 'graduated' => 0, 'transferred' => 0, 'errors' => []];

        foreach ($data['actions'] as $enrollmentId => $action) {
            $enrollment = Enrollment::with('student.ledger', 'section')->find($enrollmentId);

            if (!$enrollment || $enrollment->status !== 'Active') {
                $results['errors'][] = "Enrollment #{$enrollmentId} not found or not active.";
                continue;
            }

            try {
                match ($action) {
                    'promote' => $this->promote($enrollment, $data['school_year']),
                    'retain' => $this->retain($enrollment, $data['school_year']),
                    'graduate' => $this->graduate($enrollment),
                    'transfer' => $this->transfer($enrollment),
                };
                $results[$action === 'promote' ? 'promoted' : ($action === 'retain' ? 'retained' : ($action === 'graduate' ? 'graduated' : 'transferred'))]++;
            } catch (\Exception $e) {
                $results['errors'][] = "{$enrollment->student?->first_name} {$enrollment->student?->last_name}: {$e->getMessage()}";
            }
        }

        $message = "Processed: {$results['promoted']} promoted, {$results['retained']} retained, {$results['graduated']} graduated, {$results['transferred']} transferred.";
        if ($results['errors']) {
            $message .= ' Errors: ' . implode(' | ', $results['errors']);
        }

        return redirect()->route('admin.promotion.index')->with('success', $message);
    }

    private function promote(Enrollment $enrollment, string $newSchoolYear)
    {
        $currentGrade = $enrollment->section?->grade_level;
        $nextGrade = $this->getNextGradeLevel($currentGrade);

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

        $this->carryFees($student, $nextGrade, $newSchoolYear);

        $enrollment->status = 'Promoted';
        $enrollment->promoted_to_enrollment_id = $newEnrollment->id;
        $enrollment->save();
    }

    private function retain(Enrollment $enrollment, string $newSchoolYear)
    {
        $currentGrade = $enrollment->section?->grade_level;

        $section = Section::where('grade_level', $currentGrade)->where('is_active', true)->first();
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

        $this->carryFees($student, $currentGrade, $newSchoolYear);

        $enrollment->status = 'Promoted';
        $enrollment->promoted_to_enrollment_id = $newEnrollment->id;
        $enrollment->save();
    }

    private function graduate(Enrollment $enrollment)
    {
        $enrollment->status = 'Graduated';
        $enrollment->save();

        $enrollment->student->status = 'graduated';
        $enrollment->student->save();
    }

    private function transfer(Enrollment $enrollment)
    {
        $enrollment->status = 'Withdrawn';
        $enrollment->save();

        $enrollment->student->status = 'archived';
        $enrollment->student->save();
    }

    private function carryFees(Student $student, string $gradeLevel, string $schoolYear)
    {
        $ledger = $student->ledger;

        if (!$ledger) {
            return;
        }

        $oldBalance = $ledger->balance;

        $semesterFees = FeeSchedule::where('grade_level', $gradeLevel)
            ->where('school_year', $schoolYear)
            ->get();

        $newFeesTotal = $semesterFees->sum(fn($f) => $f->tuition_fee + $f->misc_fee);

        if ($newFeesTotal > 0 || $oldBalance > 0) {
            $ledger->carried_over_balance = $oldBalance;
            $ledger->total_assessed += $newFeesTotal;
            $ledger->balance += $newFeesTotal;
            $ledger->clearance_status = 'Uncleared';
            $ledger->it_confirmed_at = null;
            $ledger->save();
        }
    }

    private function getNextGradeLevel(?string $current): ?string
    {
        return match ($current) {
            'Grade 7' => 'Grade 8',
            'Grade 8' => 'Grade 9',
            'Grade 9' => 'Grade 10',
            'Grade 10' => 'Grade 11',
            'Grade 11' => 'Grade 12',
            'Grade 12' => null,
            default => null,
        };
    }
}
