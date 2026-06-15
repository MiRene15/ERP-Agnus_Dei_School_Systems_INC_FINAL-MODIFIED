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

        if ($activeEnrollment && $activeEnrollment->school_year === date('Y') . '-' . (date('Y') + 1)) {
            return redirect()->route('student.dashboard')
                ->with('info', 'You are already enrolled for the current school year.');
        }

        $pendingAdmission = $student->admissions()
            ->where('application_type', 'Old')
            ->where('status', 'Pending')
            ->latest()
            ->first();

        $nextGradeLevel = $activeEnrollment ? $this->nextGradeLevel($activeEnrollment->section->grade_level) : null;

        return view('portal.student.enrollment-apply', compact('student', 'activeEnrollment', 'pendingAdmission', 'nextGradeLevel'));
    }

    public function store(Request $request)
    {
        $student = auth()->user()->student;

        if (!$student->student_number) {
            return back()->with('error', 'You must have a student number to enroll.');
        }

        $data = $request->validate([
            'grade_level' => 'required|string|max:20',
            'school_year' => 'required|string|max:20',
        ]);

        Admission::create([
            'student_id' => $student->id,
            'application_type' => 'Old',
            'grade_level' => $data['grade_level'],
            'school_year' => $data['school_year'],
            'status' => 'Pending',
        ]);

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
