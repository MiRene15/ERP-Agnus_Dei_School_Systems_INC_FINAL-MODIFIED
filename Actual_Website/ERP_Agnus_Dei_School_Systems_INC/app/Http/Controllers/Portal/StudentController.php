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

        if (!$student) {
            $msg = 'No student profile found for this account. Please contact the registrar.';
            if ($isAjax) {
                return response()->json(['html' => '<div class="p-8 text-center text-sm text-red-600">'.$msg.'</div>']);
            }
            return view('portal.student.dashboard', ['student' => null, 'activeEnrollment' => null, 'pendingAdmission' => null, 'schoolYear' => null, 'schoolYears' => collect()])->with('error', $msg);
        }

        $pendingAdmission = $student->admissions()->where('status', 'Pending')->latest()->first();

        // Spec: future-year-enrolled-dashboard — the student sees only years they were
        // ever part of (own enrollments + pending admission year). Never merge every
        // system year, so a first-time 2027-2028 student never sees an empty 2026-2027 row.
        $ownYears = $student->enrollments()->distinct()->pluck('school_year');
        if ($pendingAdmission?->school_year) {
            $ownYears = $ownYears->merge([$pendingAdmission->school_year]);
        }
        $schoolYears = $ownYears->filter()->unique()->sortDesc()->values();

        // Default to the student's latest Active enrollment year (so a 2027-2028-only
        // student lands on 2027-2028, and a both-years student lands on 2027-2028),
        // instead of always defaulting to the active (2026-2027) year.
        $latestActiveYear = $student->enrollments()->where('status', 'Active')->max('school_year');
        $defaultYear = ($latestActiveYear && $schoolYears->contains($latestActiveYear))
            ? $latestActiveYear
            : ($schoolYears->first() ?? active_school_year());
        $requestedYear = $request->input('school_year');
        $schoolYear = ($requestedYear && $schoolYears->contains($requestedYear))
            ? $requestedYear
            : $defaultYear;

        // Only an Active (registrar-approved) enrollment counts as Enrolled.
        // Cancelled / Withdrawn / Transferred / Refunded / Dropped never show as Enrolled.
        $activeEnrollment = $student->enrollments()
            ->with('section', 'subjects')
            ->where('school_year', $schoolYear)
            ->where('status', 'Active')
            ->latest()
            ->first();

        // Years with an Active enrollment, newest first — for the "also enrolled" line
        // when a student holds both 2026-2027 and 2027-2028.
        $enrolledSchoolYears = $student->enrollments()
            ->where('status', 'Active')
            ->distinct()
            ->pluck('school_year')
            ->sortDesc()
            ->values();

        // Balance line: fees really posted for this grade + year? Never copy/invent
        // a balance from another year — when none exist we say "No fees posted yet".
        $hasFeesPosted = $activeEnrollment
            ? \App\Models\FeeSchedule::where('grade_level', $activeEnrollment->section->grade_level)
                ->where('school_year', $activeEnrollment->school_year)
                ->exists()
            : false;

        $student->load('ledger');

        // Clearance holds (library / clinic / finance) — student sees why they are blocked.
        $holds = \App\Services\HoldService::forStudent($student);

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.student.partials.dashboard-results', compact('student', 'activeEnrollment', 'pendingAdmission', 'schoolYear', 'schoolYears', 'holds', 'hasFeesPosted', 'enrolledSchoolYears'))->render(),
            ]);
        }

        return view('portal.student.dashboard', compact('student', 'activeEnrollment', 'pendingAdmission', 'schoolYear', 'schoolYears', 'holds', 'hasFeesPosted', 'enrolledSchoolYears'));
    }

    public function cor(Request $request)
    {
        $student = auth()->user()->student;
        if (!$student) return redirect()->route('student.dashboard')->with('error', 'No student profile found. Contact registrar.');

        $schoolYear = $request->input('school_year', active_school_year());
        $schoolYears = $student->enrollments()->distinct()->pluck('school_year')->merge(all_school_years())->unique()->sortDesc()->values();
        $currentTerm = Setting::getValue('current_term', '1st Term');
        $enrollment = $student->enrollments()
            ->with(['section', 'subjects.subject', 'subjects.schedules', 'subjects.teacher', 'promotedToEnrollment'])
            ->where('school_year', $schoolYear)
            ->latest()
            ->first();

        if (!$enrollment) {
            return redirect()->route('student.dashboard')->with('error', 'No active enrollment found.');
        }

        // Filter by current term and deduplicate by subject (one row per subject, not per class row)
        $filtered = $enrollment->subjects->filter(function($cls) use ($currentTerm) {
            return empty($cls->term) || $cls->term === $currentTerm;
        });
        if ($filtered->isEmpty()) $filtered = $enrollment->subjects;
        $deduped = $filtered->unique(fn($c) => $c->subject->name ?? $c->subject_id)->values();
        $enrollment->setRelation('subjects', $deduped);

        $ledger = $student->ledger;

        $feeSchedules = \App\Models\FeeSchedule::where('grade_level', $enrollment->section->grade_level)
            ->where('school_year', $enrollment->school_year)
            ->orderBy('term')
            ->get();

        $directressName = Setting::getValue('directress_name', '');
        $principalName = Setting::getValue('principal_name', '');

        return view('portal.student.cor', compact('student', 'enrollment', 'ledger', 'feeSchedules', 'directressName', 'principalName', 'schoolYear', 'schoolYears'));
    }

    public function schedule(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $student = auth()->user()->student;
        if (!$student) return redirect()->route('student.dashboard')->with('error', 'No student profile found.');

        $schoolYear = $request->input('school_year', active_school_year());
        $schoolYears = $student->enrollments()->distinct()->pluck('school_year')->merge(all_school_years())->unique()->sortDesc()->values();

        $activeEnrollment = $student->enrollments()
            ->with('section', 'subjects.subject', 'subjects.schedules', 'subjects.teacher')
            ->where('school_year', $schoolYear)
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
                'html' => view('portal.student.partials.schedule-results', compact('activeEnrollment', 'scheduleSlots', 'schoolYear', 'schoolYears'))->render(),
            ]);
        }

        return view('portal.student.schedule', compact('student', 'activeEnrollment', 'scheduleSlots', 'schoolYear', 'schoolYears'));
    }

    public function ledger(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $student = auth()->user()->student;
        if (!$student) return redirect()->route('student.dashboard')->with('error', 'No student profile found.');

        $schoolYear = $request->input('school_year', active_school_year());
        $schoolYears = $student->enrollments()->distinct()->pluck('school_year')->merge(all_school_years())->unique()->sortDesc()->values();

        $activeEnrollment = $student->enrollments()
            ->with('section')
            ->where('school_year', $schoolYear)
            ->latest()
            ->first();

        if (!$activeEnrollment) {
            if ($isAjax) {
                return response()->json(['html' => '<div class="p-8 text-center text-sm text-gray-500">No active enrollment found.</div>']);
            }
            return redirect()->route('student.dashboard')->with('error', 'No active enrollment found.');
        }

        $student->load('ledger.payments');
        $feeSchedules = \App\Models\FeeSchedule::where('grade_level', $activeEnrollment->section->grade_level)
            ->where('school_year', $activeEnrollment->school_year)
            ->orderBy('term')->get();

        // Latest approved/applied discount request, so the student sees the full breakdown.
        $discountRequest = $student->ledger
            ? \App\Models\DiscountRequest::with('reviewer')->where('student_ledger_id', $student->ledger->id)
                ->whereIn('status', [\App\Models\DiscountRequest::STATUS_APPLIED, \App\Models\DiscountRequest::STATUS_APPROVED])
                ->latest()->first()
            : null;

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.student.partials.ledger-results', compact('student', 'activeEnrollment', 'feeSchedules', 'schoolYear', 'schoolYears', 'discountRequest'))->render(),
            ]);
        }

        return view('portal.student.ledger', compact('student', 'activeEnrollment', 'feeSchedules', 'schoolYear', 'schoolYears', 'discountRequest'));
    }
}
