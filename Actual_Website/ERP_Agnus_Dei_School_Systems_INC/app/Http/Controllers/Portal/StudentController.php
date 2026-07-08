<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
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
            ? \App\Models\Grade::whereHas('class', function ($q) use ($activeEnrollment) {
                $q->whereIn('id', $activeEnrollment->subjects->pluck('id'));
            })->where('enrollment_id', $activeEnrollment->id)->with('class.subject')->get()
            : collect();

        return view('portal.student.dashboard', compact('student', 'activeEnrollment', 'pendingAdmission', 'grades'));
    }
}
