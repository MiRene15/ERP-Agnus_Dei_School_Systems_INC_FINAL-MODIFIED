<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;

class RegistrarAdmissionController extends Controller
{
    public function index()
    {
        $admissions = Admission::with('student.user')
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

        return view('portal.registrar.admissions-show', compact('admission', 'sections'));
    }

    public function approve(Request $request, Admission $admission)
    {
        if ($admission->status !== 'Pending') {
            return back()->with('error', 'This admission has already been processed.');
        }

        $data = $request->validate([
            'section_id' => 'required|exists:sections,id',
        ]);

        $student = $admission->student;

        if ($admission->application_type !== 'Old') {
            $student->student_number = Student::generateStudentNumber();
        }

        $student->status = 'enrolled';
        $student->save();

        Enrollment::create([
            'student_id' => $student->id,
            'section_id' => $data['section_id'],
            'school_year' => $admission->school_year,
            'status' => 'Active',
        ]);

        $admission->status = 'Approved By Registrar';
        $admission->save();

        return redirect()->route('registrar.admissions.index')
            ->with('success', 'Admission approved for ' . $student->first_name . ' ' . $student->last_name . '.');
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
