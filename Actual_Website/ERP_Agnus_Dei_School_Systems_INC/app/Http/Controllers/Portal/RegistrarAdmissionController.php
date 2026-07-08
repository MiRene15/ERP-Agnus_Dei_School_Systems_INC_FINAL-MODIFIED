<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Requirement;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;

class RegistrarAdmissionController extends Controller
{
    public function index()
    {
        $admissions = Admission::with('student.user')
            ->where('school_year', active_school_year())
            ->orderByRaw("FIELD(status, 'Pending') DESC")
            ->latest()
            ->get();

        $pendingCount = $admissions->where('status', 'Pending')->count();
        $approvedCount = $admissions->where('status', 'Approved By Registrar')->count();

        return view('portal.registrar.admissions-index', compact('admissions', 'pendingCount', 'approvedCount'));
    }

    public function show(Admission $admission)
    {
        $admission->load('student.user', 'requirements');
        $sections = Section::where('is_active', true)
            ->where('grade_level', $admission->grade_level)
            ->get();

        $classes = Classes::with('subject', 'teacher')
            ->where('grade_level', $admission->grade_level)
            ->where('status', 'active')
            ->get();

        return view('portal.registrar.admissions-show', compact('admission', 'sections', 'classes'));
    }

    public function verifyRequirement(Request $request, Requirement $requirement)
    {
        $requirement->status = $request->input('verify') ? 'Verified' : 'Under Review';
        $requirement->save();

        return back()->with('success', 'Requirement ' . ($requirement->status === 'Verified' ? 'verified' : 'unverified') . '.');
    }

    public function approve(Request $request, Admission $admission)
    {
        if ($admission->status !== 'Pending') {
            return back()->with('error', 'This admission has already been processed.');
        }

        $data = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:classes,id',
        ]);

        foreach ($data['subject_ids'] as $classId) {
            $class = Classes::find($classId);
            if ($class && $class->capacity) {
                $enrolledCount = $class->enrollments()
                    ->where('status', 'Active')
                    ->count();
                if ($enrolledCount >= $class->capacity) {
                    return back()->with('error', 'Subject "' . ($class->subject->name ?? 'N/A') . '" has reached its capacity of ' . $class->capacity . ' students.');
                }
            }
        }

        $student = $admission->student;

        if ($admission->application_type !== 'Old') {
            $student->student_number = Student::generateStudentNumber();
        }

        $student->status = 'enrolled';
        $student->save();

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'section_id' => $data['section_id'],
            'school_year' => $admission->school_year,
            'strand' => $admission->strand,
            'status' => 'Active',
        ]);

        $enrollment->subjects()->attach($data['subject_ids']);

        $admission->status = 'Approved By Registrar';
        $admission->save();

        return redirect()->route('registrar.admissions.index')
            ->with('success', 'Admission approved for ' . $student->first_name . ' ' . $student->last_name . '. Student has been enrolled with ' . count($data['subject_ids']) . ' subject(s).');
    }

    public function reject(Admission $admission)
    {
        if ($admission->status !== 'Pending') {
            return back()->with('error', 'This admission has already been processed.');
        }

        $admission->status = 'Rejected';
        $admission->save();

        return redirect()->route('registrar.admissions.index')
            ->with('success', 'Admission application has been rejected.');
    }
}
