<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentLedger;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $pendingConfirmations = StudentLedger::whereNull('it_confirmed_at')
            ->where('clearance_status', 'Cleared')
            ->with('student.user')
            ->get();

        $confirmedCount = StudentLedger::whereNotNull('it_confirmed_at')->count();
        $totalUsers = \App\Models\User::count();
        $activeRoles = \App\Models\Role::count();

        $recentActivity = \App\Models\ActivityLog::with('causer')->latest()->take(5)->get();

        return view('portal.admin.dashboard', compact('pendingConfirmations', 'confirmedCount', 'totalUsers', 'activeRoles', 'recentActivity'));
    }

    public function pendingAccounts()
    {
        $pendingConfirmations = StudentLedger::whereNull('it_confirmed_at')
            ->where('clearance_status', 'Cleared')
            ->with('student.user', 'student.enrollments.section')
            ->get();

        return view('portal.admin.pending-accounts', compact('pendingConfirmations'));
    }

    public function confirmAccount(StudentLedger $ledger)
    {
        if ($ledger->clearance_status !== 'Cleared') {
            return back()->with('error', 'Student must have cleared payments before IT confirmation.');
        }

        $ledger->it_confirmed_at = now();
        $ledger->save();

        return back()->with('success', 'Account confirmed for ' . $ledger->student->first_name . ' ' . $ledger->student->last_name . '.');
    }

    public function settings()
    {
        $activeSY = active_school_year();
        $directressName = Setting::getValue('directress_name', '');
        $principalName = Setting::getValue('principal_name', '');
        return view('portal.admin.settings', compact('activeSY', 'directressName', 'principalName'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'active_school_year' => 'required|string|max:20',
            'directress_name'    => 'nullable|string|max:100',
            'principal_name'     => 'nullable|string|max:100',
        ]);

        Setting::setValue('active_school_year', $data['active_school_year']);
        Setting::setValue('directress_name', $data['directress_name'] ?? '');
        Setting::setValue('principal_name', $data['principal_name'] ?? '');

        return back()->with('success', 'Settings saved successfully.');
    }
}
