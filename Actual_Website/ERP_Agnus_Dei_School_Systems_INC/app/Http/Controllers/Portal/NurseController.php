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
            $query->whereHas('student', function ($q) {
                $q->where('first_name', 'like', '%' . request('search') . '%')
                    ->orWhere('last_name', 'like', '%' . request('search') . '%');
            });
        }

        if (request('incident_type') && request('incident_type') !== 'All') {
            $type = request('incident_type');
            $query->where(function ($q) use ($type) {
                $q->where('complaint', 'like', "%{$type}%")
                    ->orWhere('symptoms', 'like', "%{$type}%")
                    ->orWhere('diagnosis', 'like', "%{$type}%");
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
            'incident_date' => 'required|date',
            'complaint' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment' => 'nullable|string',
            'notes' => 'nullable|string',
            'referred_to' => 'nullable|string|max:255',
        ]);

        $data['nurse_id'] = auth()->id();
        $data['symptoms'] = $data['complaint'] ?? '';
        $data['visit_date'] = $data['incident_date'];

        ClinicLog::create($data);

        return redirect()->route('nurse.logs')->with('success', 'Clinic log created successfully.');
    }
}
