<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClinicLog;
use App\Models\Student;
use Illuminate\Http\Request;

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
}
