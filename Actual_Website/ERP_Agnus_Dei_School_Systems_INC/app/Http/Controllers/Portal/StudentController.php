<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $student = auth()->user()->student;

        $student->load('ledger.payments');

        $activeEnrollment = $student->enrollments()
            ->with('section', 'subjects.subject', 'subjects.schedules', 'subjects.teacher')
            ->where('status', 'Active')
            ->latest()
            ->first();

        $pendingAdmission = $student->admissions()->where('status', 'Pending')->latest()->first();

        $grades = $activeEnrollment
            ? \App\Models\Grade::whereHas('schoolClass', function ($q) use ($activeEnrollment) {
                $q->whereIn('id', $activeEnrollment->subjects->pluck('id'));
            })->where('enrollment_id', $activeEnrollment->id)->with('schoolClass.subject')->get()
            : collect();

        return view('portal.student.dashboard', compact('student', 'activeEnrollment', 'pendingAdmission', 'grades'));
    }

    public function cor()
    {
        $student = auth()->user()->student;

        $enrollment = $student->enrollments()
            ->with('section', 'subjects.subject', 'subjects.schedules', 'subjects.teacher', 'promotedToEnrollment')
            ->where('status', 'Active')
            ->latest()
            ->first();

        if (!$enrollment) {
            return redirect()->route('student.dashboard')->with('error', 'No active enrollment found.');
        }

        $ledger = $student->ledger;

        $directressName = Setting::getValue('directress_name', '');
        $principalName = Setting::getValue('principal_name', '');

        return view('portal.student.cor', compact('student', 'enrollment', 'ledger', 'directressName', 'principalName'));
    }
}
