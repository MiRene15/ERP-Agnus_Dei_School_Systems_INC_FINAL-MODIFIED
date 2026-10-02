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

        // Work queues from the newer workflows, so nothing waits unnoticed.
        $pendingWithdrawals = \App\Models\Withdrawal::where('status', 'Pending')->count();
        $pendingUnlocks = \App\Models\GradeUnlockRequest::where('status', \App\Models\GradeUnlockRequest::STATUS_PENDING)->count();
        $missingLedgers = Enrollment::where('status', 'Active')
            ->where('school_year', active_school_year())
            ->whereDoesntHave('student.ledger')
            ->count();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.registrar.partials.dashboard-results', compact('pendingCount', 'enrolledCount', 'recentAdmissions', 'pendingWithdrawals', 'pendingUnlocks', 'missingLedgers'))->render(),
            ]);
        }

        return view('portal.registrar.dashboard', compact('pendingCount', 'enrolledCount', 'recentAdmissions', 'pendingWithdrawals', 'pendingUnlocks', 'missingLedgers'));
    }
}
