<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $student = auth()->user()->student;

        $activeEnrollment = $student->enrollments()
            ->with('section', 'subjects')
            ->where('status', 'Active')
            ->latest()
            ->first();

        $pendingAdmission = $student->admissions()->where('status', 'Pending')->latest()->first();

        $student->load('ledger');

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.student.partials.dashboard-results', compact('student', 'activeEnrollment', 'pendingAdmission'))->render(),
            ]);
        }

        return view('portal.student.dashboard', compact('student', 'activeEnrollment', 'pendingAdmission'));
    }

    public function cor()
    {
        $student = auth()->user()->student;

        $enrollment = $student->enrollments()
            ->with('section', 'subjects.subject', 'subjects.schedules', 'subjects.teacher', 'promotedToEnrollment')
            ->where('status', 'Active')
            ->latest()
            ->first();

        if (!$enrollment) {
            return redirect()->route('student.dashboard')->with('error', 'No active enrollment found.');
        }

        $ledger = $student->ledger;

        $feeSchedules = \App\Models\FeeSchedule::where('grade_level', $enrollment->section->grade_level)
            ->where('school_year', $enrollment->school_year)
            ->orderBy('term')
            ->get();

        $directressName = Setting::getValue('directress_name', '');
        $principalName = Setting::getValue('principal_name', '');

        return view('portal.student.cor', compact('student', 'enrollment', 'ledger', 'feeSchedules', 'directressName', 'principalName'));
    }

    public function schedule(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $student = auth()->user()->student;
        $activeEnrollment = $student->enrollments()
            ->with('section', 'subjects.subject', 'subjects.schedules', 'subjects.teacher')
            ->where('status', 'Active')
            ->latest()
            ->first();

        if (!$activeEnrollment) {
            if ($isAjax) {
                return response()->json(['html' => '<div class="p-8 text-center text-sm text-gray-500">No active enrollment found.</div>']);
            }
            return redirect()->route('student.dashboard')->with('error', 'No active enrollment found.');
        }

        $scheduleSlots = [];
        $slotMap = [];
        foreach ($activeEnrollment->subjects as $class) {
            $subjectName = $class->subject->name ?? 'N/A';
            $teacherName = $class->teacher->name ?? 'N/A';
            foreach ($class->schedules as $sched) {
                $timeKey = \Carbon\Carbon::parse($sched->start_time)->format('H:i') . '-' . \Carbon\Carbon::parse($sched->end_time)->format('H:i');
                if (!isset($slotMap[$timeKey])) {
                    $slotMap[$timeKey] = [
                        'time' => \Carbon\Carbon::parse($sched->start_time)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($sched->end_time)->format('h:i A'),
                        'start' => $sched->start_time,
                        'label' => '',
                        'days' => [],
                    ];
                }
                $slotMap[$timeKey]['days'][$sched->day_of_week] = [
                    'subject' => $subjectName,
                    'teacher' => $teacherName,
                ];
            }
        }
        uasort($slotMap, fn($a, $b) => strcmp($a['start'], $b['start']));
        $scheduleSlots = array_values($slotMap);

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.student.partials.schedule-results', compact('activeEnrollment', 'scheduleSlots'))->render(),
            ]);
        }

        return view('portal.student.schedule', compact('student', 'activeEnrollment', 'scheduleSlots'));
    }

    public function ledger(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $student = auth()->user()->student;
        $activeEnrollment = $student->enrollments()
            ->with('section')
            ->where('status', 'Active')
            ->latest()
            ->first();

        if (!$activeEnrollment) {
            if ($isAjax) {
                return response()->json(['html' => '<div class="p-8 text-center text-sm text-gray-500">No active enrollment found.</div>']);
            }
            return redirect()->route('student.dashboard')->with('error', 'No active enrollment found.');
        }

        $student->load('ledger.payments');

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.student.partials.ledger-results', compact('student', 'activeEnrollment'))->render(),
            ]);
        }

        return view('portal.student.ledger', compact('student', 'activeEnrollment'));
    }
}
