<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeSchedule;
use Illuminate\Http\Request;

class FeeScheduleController extends Controller
{
    public function index()
    {
        $fees = FeeSchedule::orderBy('grade_level')->orderBy('semester')->get()->groupBy('grade_level');
        $semesters = ['1st Semester', '2nd Semester', '3rd Semester'];
        return view('portal.admin.fees.index', compact('fees', 'semesters'));
    }

    public function create()
    {
        $gradeLevels = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $semesters = ['1st Semester', '2nd Semester', '3rd Semester'];
        return view('portal.admin.fees.create', compact('gradeLevels', 'semesters'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'grade_level' => 'required|string|max:20',
            'semester' => 'required|in:1st Semester,2nd Semester,3rd Semester',
            'tuition_fee' => 'required|numeric|min:0',
            'misc_fee' => 'required|numeric|min:0',
            'school_year' => 'required|string|max:20',
        ]);

        $exists = FeeSchedule::where('grade_level', $data['grade_level'])
            ->where('semester', $data['semester'])
            ->where('school_year', $data['school_year'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'A fee schedule already exists for this grade level, semester, and school year.');
        }

        FeeSchedule::create($data);

        return redirect()->route('admin.fees.index')
            ->with('success', 'Fee schedule created for ' . $data['grade_level'] . ' - ' . $data['semester'] . '.');
    }

    public function edit(FeeSchedule $fee)
    {
        $gradeLevels = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $semesters = ['1st Semester', '2nd Semester', '3rd Semester'];
        return view('portal.admin.fees.edit', compact('fee', 'gradeLevels', 'semesters'));
    }

    public function update(Request $request, FeeSchedule $fee)
    {
        $data = $request->validate([
            'grade_level' => 'required|string|max:20',
            'semester' => 'required|in:1st Semester,2nd Semester,3rd Semester',
            'tuition_fee' => 'required|numeric|min:0',
            'misc_fee' => 'required|numeric|min:0',
            'school_year' => 'required|string|max:20',
        ]);

        $fee->update($data);

        return redirect()->route('admin.fees.index')
            ->with('success', 'Fee schedule updated.');
    }

    public function destroy(FeeSchedule $fee)
    {
        $fee->delete();
        return back()->with('success', 'Fee schedule deleted.');
    }
}
