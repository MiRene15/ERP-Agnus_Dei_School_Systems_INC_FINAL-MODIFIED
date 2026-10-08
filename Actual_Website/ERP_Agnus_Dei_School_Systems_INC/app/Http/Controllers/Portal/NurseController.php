<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClinicLog;
use App\Models\Student;
use App\Services\ClinicReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NurseController extends Controller
{
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $todayVisits = ClinicLog::whereDate('incident_date', today())->count();
        $thisWeekVisits = ClinicLog::whereBetween('incident_date', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $referralsCount = ClinicLog::whereNotNull('referred_to')->count();
        $followUps = ClinicLog::whereDate('incident_date', '>=', now()->subDays(7))->count();

        $recentLogs = ClinicLog::with('student')
            ->latest('incident_date')
            ->take(5)
            ->get();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.nurse.partials.dashboard-results', compact('todayVisits', 'thisWeekVisits', 'referralsCount', 'followUps', 'recentLogs'))->render(),
            ]);
        }

        return view('portal.nurse.dashboard', compact('todayVisits', 'thisWeekVisits', 'referralsCount', 'followUps', 'recentLogs'));
    }

    public function logs(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = ClinicLog::with('student');

        if (request('search')) {
            $search = request('search');
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ["%{$search}%"]);
            });
        }

        if (request('incident_type') && request('incident_type') !== 'All') {
            $type = request('incident_type');
            $query->where(function ($q) use ($type) {
                $q->where('complaint', 'ilike', "%{$type}%")
                    ->orWhere('symptoms', 'ilike', "%{$type}%")
                    ->orWhere('diagnosis', 'ilike', "%{$type}%");
            });
        }

        if (request('sickness') && request('sickness') !== 'All') {
            $sickness = request('sickness');
            $query->where('diagnosis', 'ilike', "%{$sickness}%");
        }

        if (request('month') && request('month') !== 'All') {
            $month = (int) request('month');
            $query->whereMonth('incident_date', $month);
        }

        if (request('grade_level') && request('grade_level') !== 'All') {
            $gradeLevel = request('grade_level');
            $query->whereHas('student.enrollments.section', function ($q) use ($gradeLevel) {
                $q->where('grade_level', $gradeLevel)
                    ->where('school_year', active_school_year());
            });
        }

        if (request('date_from')) {
            $query->whereDate('incident_date', '>=', request('date_from'));
        }

        if (request('date_to')) {
            $query->whereDate('incident_date', '<=', request('date_to'));
        }

        $logs = $query->latest('incident_date')->paginate(20)->withQueryString();
        $logs->appends(request()->query());

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.nurse.partials.logs-results', compact('logs'))->render(),
            ]);
        }

        return view('portal.nurse.logs', compact('logs'));
    }

    public function createLog()
    {
        $students = Student::where('status', 'enrolled')
            ->orderBy('last_name')
            ->get();

        return view('portal.nurse.create-log', compact('students'));
    }

    public function storeLog(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'incident_date' => 'required|date|after_or_equal:1987-01-01|before_or_equal:today',
            'complaint' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment' => 'nullable|string',
            'notes' => 'nullable|string',
            'referred_to' => 'nullable|string|max:255',
            'is_open' => 'nullable|boolean',
        ], [
            'incident_date.after_or_equal' => 'Visit date is too far back.',
            'incident_date.before_or_equal' => "Visit date can't be in the future.",
        ]);

        $data['nurse_id'] = auth()->id();
        $data['symptoms'] = $data['complaint'] ?? '';
        $data['visit_date'] = $data['incident_date'];
        // Open cases (pending follow-up / referral) raise a clearance Hold until closed.
        $data['is_open'] = $request->boolean('is_open');

        $log = ClinicLog::create($data);

        $student = Student::find($data['student_id']);
        log_activity($log, 'Clinic Log Created', auth()->user()->name . ' recorded a clinic visit for ' . $student->first_name . ' ' . $student->last_name . '. Complaint: ' . ($data['complaint'] ?? 'N/A') . ($data['is_open'] ? ' [OPEN CASE — clearance hold raised]' : ''));

        return redirect()->route('nurse.logs')->with('success', 'Clinic log created successfully.' . ($data['is_open'] ? ' Case left open — it now blocks the student’s clearance until closed.' : ''));
    }

    /**
     * Close an open case — lifts the student's clinic hold.
     */
    public function closeCase(ClinicLog $log)
    {
        if (!$log->is_open) {
            return back()->with('info', 'This case is already closed.');
        }

        $log->update(['is_open' => false, 'closed_at' => now()]);

        log_activity($log, 'Clinic Case Closed', auth()->user()->name . ' closed the open clinic case for student #' . $log->student_id . ' — clearance hold lifted.');

        return back()->with('success', 'Case closed — the student’s clinic hold is lifted.');
    }

    /**
     * Clinic reports — nurse single reusing the directress totals path (spec: nurse-clinic-reports.md).
     * Hardcoded to clinic only; no tab switching. Rows limited to what logs already show.
     */
    public function reports(Request $request, ClinicReportService $service): View|JsonResponse
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');

        $data = $service->rangeData($dateFrom, $dateTo);

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('portal.nurse.partials.reports-results', $data)->render(),
            ]);
        }

        return view('portal.nurse.reports', $data);
    }

    /**
     * Export the shown range: same aggregates as the directress CSV plus the visit
     * rows already visible on this page. Recorded per-role in the audit trail.
     */
    public function exportClinicReport(Request $request, ClinicReportService $service): StreamedResponse
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');

        $data = $service->rangeData($dateFrom, $dateTo);
        $logs = $data['logs'];
        $byGrade = $data['byGrade'];
        $topSymptoms = $logs->pluck('symptoms')->filter()->flatMap(fn($s) => array_map('trim', explode(',', $s)))
            ->countBy()->sortDesc()->take(10);

        $filename = 'clinic_report_' . $dateFrom . '_to_' . $dateTo . '.csv';
        log_activity(\App\Models\ClinicLog::class, 'Exported', auth()->user()->name . ' exported the clinic report CSV (nurse single, ' . $logs->count() . ' visit(s), ' . $dateFrom . ' to ' . $dateTo . ').');

        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];
        $callback = function () use ($logs, $byGrade, $topSymptoms, $dateFrom, $dateTo) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Clinic Report', $dateFrom . ' to ' . $dateTo]);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Visits', $logs->count()]);
            fputcsv($file, ['Unique Patients', $logs->pluck('student_id')->unique()->count()]);
            fputcsv($file, ['Referred Out', $logs->whereNotNull('referred_to')->count()]);
            fputcsv($file, ['Open Cases', $logs->where('is_open', true)->count()]);
            fputcsv($file, []);
            fputcsv($file, ['Visits by Grade', 'Count']);
            foreach ($byGrade as $grade => $count) {
                fputcsv($file, [$grade, $count]);
            }
            fputcsv($file, []);
            fputcsv($file, ['Top Symptoms', 'Count']);
            foreach ($topSymptoms as $symptom => $count) {
                fputcsv($file, [$symptom, $count]);
            }
            fputcsv($file, []);
            fputcsv($file, ['Student', 'Visit Date', 'Symptoms', 'Referred To', 'Open']);
            foreach ($logs as $log) {
                fputcsv($file, [
                    trim(($log->student->first_name ?? '') . ' ' . ($log->student->last_name ?? '')),
                    $log->visit_date,
                    $log->symptoms,
                    $log->referred_to,
                    $log->is_open ? 'Yes' : 'No',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
