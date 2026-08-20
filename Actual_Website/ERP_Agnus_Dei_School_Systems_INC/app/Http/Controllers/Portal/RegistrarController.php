<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class RegistrarController extends Controller
{
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $pendingCount = Admission::where('status', 'Pending')->count();
        $enrolledCount = Enrollment::where('status', 'Active')->count();
        $recentAdmissions = Admission::with('student.user')
            ->where('status', 'Pending')
            ->latest()
            ->take(5)
            ->get();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.registrar.partials.dashboard-results', compact('pendingCount', 'enrolledCount', 'recentAdmissions'))->render(),
            ]);
        }

        return view('portal.registrar.dashboard', compact('pendingCount', 'enrolledCount', 'recentAdmissions'));
    }
}
