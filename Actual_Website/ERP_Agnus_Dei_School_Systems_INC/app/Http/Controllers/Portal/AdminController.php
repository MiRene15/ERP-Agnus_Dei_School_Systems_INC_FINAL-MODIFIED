<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcknowledgeHealthAlertRequest;
use App\Models\Setting;
use App\Models\Student;
use App\Services\SystemHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * IT Support overview: accounts, system health, audit trail.
     * Payment confirmation and academic decisions were removed from IT —
     * money belongs to Cashier, promotion/subjects to Registrar/Principal.
     */
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $totalUsers = \App\Models\User::count();
        $activeRoles = \App\Models\Role::count();
        $totalStudents = Student::count();
        $activeSY = active_school_year();

        $recentActivity = \App\Models\ActivityLog::with('causer')->latest()->take(5)->get();

        try {
            $systemHealth = SystemHealthService::overview();
        } catch (\Throwable $e) {
            $systemHealth = ['cards' => [], 'badge' => 0, 'hasData' => false];
        }

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.admin.partials.dashboard-results', compact('totalUsers', 'activeRoles', 'totalStudents', 'activeSY', 'recentActivity', 'systemHealth'))->render(),
            ]);
        }

        return view('portal.admin.dashboard', compact('totalUsers', 'activeRoles', 'totalStudents', 'activeSY', 'recentActivity', 'systemHealth'));
    }

    public function settings()
    {
        $activeSY = active_school_year();
        $schoolYears = all_school_years();
        $lockedYears = Setting::getValue('locked_school_years', '');
        $lockedYearsList = $lockedYears ? array_map('trim', explode(',', $lockedYears)) : [];
        $isCurrentYearLocked = in_array($activeSY, $lockedYearsList);
        return view('portal.admin.settings', [
            'activeSY' => $activeSY,
            'schoolYears' => $schoolYears,
            'directressName' => Setting::getValue('directress_name', ''),
            'principalName' => Setting::getValue('principal_name', ''),
            'schoolName' => Setting::getValue('school_name', 'Agnus Dei School Systems, Inc.'),
            'schoolAddress' => Setting::getValue('school_address', ''),
            'contactEmail' => Setting::getValue('contact_email', ''),
            'contactPhone' => Setting::getValue('contact_phone', ''),
            'passingGrade' => Setting::getValue('passing_grade', '75'),
            'lateFee' => Setting::getValue('library_late_fee_per_day', '5.00'),
            'damageMinor' => Setting::getValue('library_damage_minor', '50.00'),
            'damageMajor' => Setting::getValue('library_damage_major', '200.00'),
            'loanDuration' => Setting::getValue('library_loan_duration_days', '7'),
            'maxBooks' => Setting::getValue('library_max_books_per_student', '3'),
            'enrollmentOpen' => Setting::getValue('enrollment_open', '1'),
            'isCurrentYearLocked' => $isCurrentYearLocked,
            'lockedYearsList' => $lockedYearsList,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'active_school_year' => 'required|string|max:20',
            'directress_name'    => 'nullable|string|max:100',
            'principal_name'     => 'nullable|string|max:100',
            'school_name'        => 'required|string|max:150',
            'school_address'     => 'nullable|string|max:255',
            'contact_email'      => 'nullable|email|max:150',
            'contact_phone'      => 'nullable|string|max:30',
            'passing_grade'      => 'required|integer|min:50|max:100',
            'library_late_fee_per_day' => 'required|numeric|min:0|max:100',
            'library_damage_minor' => 'required|numeric|min:0|max:5000',
            'library_damage_major' => 'required|numeric|min:0|max:10000',
            'library_loan_duration_days' => 'required|integer|min:1|max:60',
            'library_max_books_per_student' => 'required|integer|min:1|max:20',
            'enrollment_open'    => 'required|in:0,1',
        ]);

        $lockedYears = Setting::getValue('locked_school_years', '');
        $lockedYearsList = $lockedYears ? array_map('trim', explode(',', $lockedYears)) : [];

        if (in_array($data['active_school_year'], $lockedYearsList)) {
            return back()->with('error', 'School year '.$data['active_school_year'].' is locked and cannot be set as active. Unlock it first via Directress > School Years.');
        }

        Setting::setValue('active_school_year', $data['active_school_year']);
        Setting::setValue('directress_name', $data['directress_name'] ?? '');
        Setting::setValue('principal_name', $data['principal_name'] ?? '');
        Setting::setValue('school_name', $data['school_name']);
        Setting::setValue('school_address', $data['school_address'] ?? '');
        Setting::setValue('contact_email', $data['contact_email'] ?? '');
        Setting::setValue('contact_phone', $data['contact_phone'] ?? '');
        Setting::setValue('passing_grade', (string) $data['passing_grade']);
        Setting::setValue('library_late_fee_per_day', (string) $data['library_late_fee_per_day']);
        Setting::setValue('library_damage_minor', (string) $data['library_damage_minor']);
        Setting::setValue('library_damage_major', (string) $data['library_damage_major']);
        Setting::setValue('library_loan_duration_days', (string) $data['library_loan_duration_days']);
        Setting::setValue('library_max_books_per_student', (string) $data['library_max_books_per_student']);
        Setting::setValue('enrollment_open', $data['enrollment_open']);

        log_activity(new Setting, 'Settings Updated', auth()->user()->name . ' updated system settings (active school year: ' . $data['active_school_year'] . ').');

        return back()->with('success', 'Settings saved successfully.');
    }

    public function auditLogs(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = \App\Models\ActivityLog::with('causer');

        if ($request->filled('user_id')) {
            if ($request->user_id === 'system') {
                $query->whereNull('causer_id');
            } else {
                $query->where('causer_id', $request->user_id);
            }
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
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'ilike', "%{$search}%")
                    ->orWhere('event', 'ilike', "%{$search}%")
                    ->orWhere('subject_type', 'ilike', "%{$search}%")
                    ->orWhereHas('causer', function ($cq) use ($search) {
                        $cq->where('name', 'ilike', "%{$search}%");
                    });
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

        $searchMetrics = \App\Services\SearchMetricsService::summary();

        return view('portal.admin.audit-logs', compact('logs', 'events', 'users', 'searchMetrics'));
    }

    public function systemHealth(Request $request): View|JsonResponse
    {
        $health = SystemHealthService::overview();
        $trends = SystemHealthService::trends();

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('portal.admin.partials.system-health-overview-results', [
                    'cards' => $health['cards'],
                    'badge' => $health['badge'],
                    'hasData' => $health['hasData'],
                    'acknowledged' => $health['acknowledged'] ?? [],
                    'ackInfo' => $health['ackInfo'] ?? [],
                    'trends' => $trends,
                ])->render(),
                'badge' => $health['badge'],
            ]);
        }

        return view('portal.admin.system-health', [
            'cards' => $health['cards'],
            'badge' => $health['badge'],
            'hasData' => $health['hasData'],
            'acknowledged' => $health['acknowledged'] ?? [],
            'ackInfo' => $health['ackInfo'] ?? [],
            'trends' => $trends,
        ]);
    }

    public function systemHealthDetail(Request $request, string $type): View|JsonResponse
    {
        if (! in_array($type, ['abuse', 'slow', 'uptime', 'logins'], true)) {
            abort(404);
        }

        $detail = SystemHealthService::forDetail($type);
        $trends = SystemHealthService::trends();

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 10;
        $rows = $detail['rows'] ?? [];
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $perPage, $perPage),
            count($rows),
            $perPage,
            $page,
            ['path' => route('admin.system-health.show', $type)]
        );

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('portal.admin.partials.system-health-detail-page-results', ['detail' => $detail, 'rows' => $paginator, 'trends' => $trends])->render(),
            ]);
        }

        return view('portal.admin.system-health-show', ['detail' => $detail, 'trends' => $trends, 'rows' => $paginator]);
    }

    public function acknowledgeHealthAlert(AcknowledgeHealthAlertRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $type = (string) ($data['alert_type'] ?? '');
        $route = (string) ($data['route'] ?? '');

        $rowsMap = [];
        try {
            $detail = SystemHealthService::forDetail($type);
            foreach ($detail['rows'] ?? [] as $row) {
                $area = (string) ($row['area'] ?? '');
                if ($route === '' || $area === $route) {
                    $rowsMap[$area] = (int) ($row['times'] ?? 0);
                }
            }
        } catch (\Throwable $e) {
            $rowsMap = [];
        }

        SystemHealthService::acknowledge(
            $type,
            $route,
            (int) ($data['counts'] ?? 0),
            (int) $request->user()->id,
            $rowsMap
        );

        if ($request->expectsJson() || $request->boolean('ajax')) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Alert acknowledged.');
    }
}
