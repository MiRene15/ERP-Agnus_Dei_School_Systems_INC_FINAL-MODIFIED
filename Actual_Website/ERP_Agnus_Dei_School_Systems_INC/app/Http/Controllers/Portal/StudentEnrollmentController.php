<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class StudentEnrollmentController extends Controller
{
    public function create()
    {
        $student = auth()->user()->student;

        if (!$student->student_number) {
            return redirect()->route('student.admission.create')
                ->with('info', 'Please complete your admission application first.');
        }

        $activeEnrollment = Enrollment::where('student_id', $student->id)
            ->where('status', 'Active')
            ->latest()
            ->first();

        if ($activeEnrollment && $activeEnrollment->school_year === active_school_year()) {
            return redirect()->route('student.dashboard')
                ->with('info', 'You are already enrolled for the current school year.');
        }

        $pendingAdmission = $student->admissions()
            ->where('application_type', 'Old')
            ->where('status', 'Pending')
            ->latest()
            ->first();

        $nextGradeLevel = $activeEnrollment ? $this->nextGradeLevel($activeEnrollment->section->grade_level) : null;
        if ($nextGradeLevel === null) {
            // Continuer path (spec promotion-section-capacity §6 amendment):
            // a Kinder/Grade 6/Grade 10 graduate re-enrolls to the next level
            // even with no Active enrollment — pre-suggest it as a returnee.
            $lastGraduated = $student->enrollments()
                ->where('status', 'Graduated')
                ->latest()
                ->with('section')
                ->first();
            if ($lastGraduated && $lastGraduated->section) {
                $nextGradeLevel = $this->nextGradeLevel($lastGraduated->section->grade_level);
            }
        }
        if ($nextGradeLevel === null && \App\Services\HoldService::isFinalGraduate($student)) {
            // Terminal Grade 12: Completed, no Enroll prompt — login stays
            // read-only; any return files a fresh admission (out of scope).
            return redirect()->route('student.dashboard')
                ->with('info', 'Completed — congratulations, graduate! Your records stay available below.');
        }

        // Holds block re-enrollment until cleared — gated: only when a new
        // school year is announced (spec: calendar-holds §6).
        $holds = \App\Services\HoldService::visibleForStudent($student);

        return view('portal.student.enrollment-apply', compact('student', 'activeEnrollment', 'pendingAdmission', 'nextGradeLevel', 'holds'));
    }

    public function store(Request $request)
    {
        $student = auth()->user()->student;

        if (!$student->student_number) {
            return back()->with('error', 'You must have a student number to enroll.');
        }

        // Holds block re-enrollment until cleared (library / clinic / finance) —
        // gated on the announced new year so nothing blocks before announcement.
        $holds = \App\Services\HoldService::visibleForStudent($student);
        if (!empty($holds)) {
            return back()->with('error', 'Enrollment request blocked. ' . \App\Services\HoldService::blockingMessage($holds));
        }

        $data = $request->validate([
            'grade_level' => 'required|string|max:20',
            'school_year' => 'required|string|max:20',
        ]);

        $admission = Admission::create([
            'student_id' => $student->id,
            'application_type' => 'Old',
            'grade_level' => $data['grade_level'],
            'school_year' => $data['school_year'],
            'status' => 'Pending',
        ]);

        log_activity($admission, 'Enrollment Request Submitted', $student->first_name . ' ' . $student->last_name . ' submitted an enrollment request for ' . $data['grade_level'] . ' (SY ' . $data['school_year'] . ').');

        return redirect()->route('student.dashboard')
            ->with('success', 'Enrollment request submitted for ' . $data['school_year'] . '. Please wait for the Registrar to approve it.');
    }

    private function nextGradeLevel(string $current): string
    {
        $map = [
            'Kinder' => 'Grade 1',
            'Grade 1' => 'Grade 2', 'Grade 2' => 'Grade 3', 'Grade 3' => 'Grade 4',
            'Grade 4' => 'Grade 5', 'Grade 5' => 'Grade 6', 'Grade 6' => 'Grade 7',
            'Grade 7' => 'Grade 8', 'Grade 8' => 'Grade 9', 'Grade 9' => 'Grade 10',
            'Grade 10' => 'Grade 11', 'Grade 11' => 'Grade 12',
        ];
        return $map[$current] ?? $current;
    }
}
