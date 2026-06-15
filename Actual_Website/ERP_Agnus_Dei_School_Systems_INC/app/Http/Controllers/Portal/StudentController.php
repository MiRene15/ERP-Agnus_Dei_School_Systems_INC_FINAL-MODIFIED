<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $student = auth()->user()->student;
        $activeEnrollment = $student->enrollments()->with('section')->where('status', 'Active')->latest()->first();
        $pendingAdmission = $student->admissions()->where('status', 'Pending')->latest()->first();

        return view('portal.student.dashboard', compact('student', 'activeEnrollment', 'pendingAdmission'));
    }
}
