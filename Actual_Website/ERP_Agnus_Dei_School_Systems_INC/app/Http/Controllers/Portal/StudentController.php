<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $student = auth()->user()->student;

        $student->load('ledger.payments');

        $activeEnrollment = $student->enrollments()
            ->with('section', 'subjects.subject', 'subjects.schedules', 'subjects.teacher')
            ->where('status', 'Active')
            ->latest()
            ->first();

        $pendingAdmission = $student->admissions()->where('status', 'Pending')->latest()->first();

        $grades = $activeEnrollment
            ? \App\Models\Grade::whereHas('schoolClass', function ($q) use ($activeEnrollment) {
                $q->whereIn('id', $activeEnrollment->subjects->pluck('id'));
            })->where('enrollment_id', $activeEnrollment->id)->with('schoolClass.subject')->get()
            : collect();

        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];

        // Build schedule slots for column layout
        $scheduleSlots = [];
        if ($activeEnrollment) {
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
        }

        return view('portal.student.dashboard', compact('student', 'activeEnrollment', 'pendingAdmission', 'grades', 'gradingPeriods', 'scheduleSlots'));
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
}
