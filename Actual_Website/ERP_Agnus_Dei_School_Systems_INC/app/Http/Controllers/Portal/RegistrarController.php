<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class RegistrarController extends Controller
{
    public function index()
    {
        $pendingCount = Admission::where('status', 'Pending')->count();
        $enrolledCount = Enrollment::where('status', 'Active')->count();
        $recentAdmissions = Admission::with('student.user')
            ->where('status', 'Pending')
            ->latest()
            ->take(5)
            ->get();

        return view('portal.registrar.dashboard', compact('pendingCount', 'enrolledCount', 'recentAdmissions'));
    }
}
