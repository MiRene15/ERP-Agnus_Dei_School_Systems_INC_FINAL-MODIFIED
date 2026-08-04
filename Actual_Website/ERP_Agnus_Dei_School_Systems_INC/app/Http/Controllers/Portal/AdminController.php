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
        $schoolYears = all_school_years();
        $directressName = Setting::getValue('directress_name', '');
        $principalName = Setting::getValue('principal_name', '');
        return view('portal.admin.settings', compact('activeSY', 'schoolYears', 'directressName', 'principalName'));
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

    public function auditLogs(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = \App\Models\ActivityLog::with('causer');

        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id);
        }
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest()->paginate(25)->withQueryString();
        $events = \App\Models\ActivityLog::distinct()->pluck('event')->filter()->sort()->values();
        $users = \App\Models\User::whereIn('id',
            \App\Models\ActivityLog::distinct()->pluck('causer_id')->filter()
        )->orderBy('name')->get();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.admin.partials.audit-logs-results', compact('logs'))->render(),
            ]);
        }

        return view('portal.admin.audit-logs', compact('logs', 'events', 'users'));
    }
}
