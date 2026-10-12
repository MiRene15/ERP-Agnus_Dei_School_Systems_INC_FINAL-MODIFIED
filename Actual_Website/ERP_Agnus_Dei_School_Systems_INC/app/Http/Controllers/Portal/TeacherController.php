<?php

namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;

use App\Http\Requests\Portal\StoreGradeAssessmentStudentRequest;
use App\Models\Assessment;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use App\Mail\GradesSubmittedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $teacherId = auth()->id();
        $schoolYear = $request->input('school_year', active_school_year());
        $schoolYears = all_school_years();
        $classes = Classes::with('subject', 'schedules', 'enrollments')
            ->where('teacher_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->where('status', 'active')
            ->get();

        $today = now()->format('l');
        $todaySchedule = $classes->flatMap->schedules->filter(function ($s) use ($today) {
            return $s->day_of_week === $today;
        })->sortBy('start_time');

        $totalStudents = $classes->sum(function ($c) {
            return $c->enrollments->where('status', 'Active')->count();
        });

        // Correction workflow status, so approved unlocks get acted on.
        $unlockPending = \App\Models\GradeUnlockRequest::where('requested_by', $teacherId)
            ->where('status', \App\Models\GradeUnlockRequest::STATUS_PENDING)
            ->count();
        $unlockApproved = \App\Models\GradeUnlockRequest::where('requested_by', $teacherId)
            ->where('status', \App\Models\GradeUnlockRequest::STATUS_APPROVED)
            ->count();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.teacher.partials.dashboard-results', compact('classes', 'todaySchedule', 'totalStudents', 'schoolYear', 'schoolYears', 'unlockPending', 'unlockApproved'))->render(),
            ]);
        }

        return view('portal.teacher.dashboard', compact('classes', 'todaySchedule', 'totalStudents', 'schoolYear', 'schoolYears', 'unlockPending', 'unlockApproved'));
    }

    public function classes(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $teacherId = auth()->id();
        $schoolYear = $request->input('school_year', active_school_year());
        $schoolYears = all_school_years();
        $query = Classes::with('subject', 'schedules', 'teacher')
            ->where('teacher_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('subject', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('subject_code', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        $classes = $query->get();
        $gradeLevels = $classes->pluck('grade_level')->unique()->sort()->values()->all();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.teacher.partials.classes-results', compact('classes', 'gradeLevels', 'schoolYear', 'schoolYears'))->render(),
            ]);
        }

        return view('portal.teacher.classes', compact('classes', 'gradeLevels', 'schoolYear', 'schoolYears'));
    }

    public function showClass(Classes $class)
    {
        if ($class->teacher_id !== auth()->id()) {
            abort(403);
        }

        $class->load('subject', 'schedules', 'enrollments.student');

        $activeEnrollments = $class->enrollments->filter(function ($e) {
            return $e->status === 'Active';
        });

        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];
        $selectedPeriod = request('grading_period', $gradingPeriods[0]);

        $existingGrades = Grade::where('class_id', $class->id)
            ->where('grading_period', $selectedPeriod)
            ->get()
            ->keyBy('enrollment_id');

        $weights = ['Written Work' => 0.20, 'Quiz' => 0.20, 'Seatwork' => 0.20, 'Exam' => 0.40];
        $assessmentTypes = ['Written Work', 'Quiz', 'Seatwork', 'Exam'];
        $allAssessments = Assessment::where('class_id', $class->id)
            ->where('grading_period', $selectedPeriod)
            ->get()
            ->groupBy('enrollment_id');

        $computedMap = [];
        foreach ($activeEnrollments as $enrollment) {
            $assessments = $allAssessments->get($enrollment->id, collect());
            $weightedSum = 0;
            foreach ($assessmentTypes as $type) {
                $typeAssessments = $assessments->where('type', $type);
                $totalRaw = $typeAssessments->sum('raw_score');
                $totalMax = $typeAssessments->sum('max_score');
                $percentage = $totalMax > 0 ? ($totalRaw / $totalMax) * 100 : 0;
                $weightedSum += $percentage * ($weights[$type] ?? 0.25);
            }
            $computedMap[$enrollment->id] = round($weightedSum, 2);
        }

        $gradingClose = \App\Services\GradingCloseService::banner($selectedPeriod);
        $gradingClosed = \App\Services\GradingCloseService::isClosed($selectedPeriod);

        return view('portal.teacher.grades', compact('class', 'activeEnrollments', 'gradingPeriods', 'selectedPeriod', 'existingGrades', 'computedMap', 'gradingClose', 'gradingClosed'));
    }

    public function storeGrades(Request $request, Classes $class)
    {
        if ($class->teacher_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'grading_period' => 'required|string|in:1st Term,2nd Term,3rd Term',
            'grades' => 'required|array',
            'grades.*' => 'nullable|numeric|min:0|max:100',
        ]);

        // Locked school years are frozen — grades can no longer be changed.
        if (school_year_locked($class->school_year)) {
            return back()->with('error', 'School year ' . $class->school_year . ' is locked — grades can no longer be changed.');
        }

        // Spec enrollment-payment-gating-calendar-holds.md §4.2: after close,
        // new saves need a Grade Edit Request (approved unlock reopens the term).
        if (\App\Services\GradingCloseService::isClosed($data['grading_period'])) {
            $hasUnlock = \App\Models\GradeUnlockRequest::where('class_id', $class->id)
                ->where('grading_period', $data['grading_period'])
                ->where('status', \App\Models\GradeUnlockRequest::STATUS_APPROVED)
                ->exists();
            if (! $hasUnlock) {
                return back()->with('error', 'Grading is closed for ' . $data['grading_period'] . ' — request an edit to change grades.');
            }
        }

        // Submitted grades are locked — only an approved unlock reopens them.
        $lockedIds = Grade::where('class_id', $class->id)
            ->where('grading_period', $data['grading_period'])
            ->where('status', 'Submitted')
            ->pluck('enrollment_id')
            ->flip();

        $skipped = 0;
        foreach ($data['grades'] as $enrollmentId => $finalGrade) {
            if ($finalGrade === null || $finalGrade === '') {
                continue;
            }
            if (isset($lockedIds[$enrollmentId])) {
                $skipped++;
                continue;
            }

            Grade::updateOrCreate(
                [
                    'enrollment_id' => $enrollmentId,
                    'class_id' => $class->id,
                    'grading_period' => $data['grading_period'],
                ],
                [
                    'final_grade' => $finalGrade,
                    'status' => 'Pending',
                ]
            );
        }

        log_activity($class, 'Grades Saved', auth()->user()->name . ' saved grades for ' . $data['grading_period'] . ' (' . $class->subject->name . ' - ' . $class->grade_level . ' ' . $class->section . '). ' . count(array_filter($data['grades'])) . ' grade(s) recorded.' . ($skipped ? " {$skipped} submitted (locked) grade(s) skipped." : ''));

        $message = 'Grades saved for ' . $data['grading_period'] . '.';
        if ($skipped) {
            $message .= " {$skipped} submitted grade(s) were skipped (locked) — request a correction unlock to change them.";
        }

        return back()->with('success', $message);
    }

    public function submitGrades(Request $request, Classes $class)
    {
        if ($class->teacher_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'grading_period' => 'required|string|in:1st Term,2nd Term,3rd Term',
        ]);

        // Locked school years are frozen — grades can no longer be changed.
        if (school_year_locked($class->school_year)) {
            return back()->with('error', 'School year ' . $class->school_year . ' is locked — grades can no longer be changed.');
        }

        if (\App\Services\GradingCloseService::isClosed($data['grading_period'])) {
            $hasUnlock = \App\Models\GradeUnlockRequest::where('class_id', $class->id)
                ->where('grading_period', $data['grading_period'])
                ->where('status', \App\Models\GradeUnlockRequest::STATUS_APPROVED)
                ->exists();
            if (! $hasUnlock) {
                return back()->with('error', 'Grading is closed for ' . $data['grading_period'] . ' — request an edit to change grades.');
            }
        }

        $gradeCount = Grade::where('class_id', $class->id)
            ->where('grading_period', $data['grading_period'])
            ->where('status', 'Pending')
            ->update(['status' => 'Submitted']);

        // Auto-cancel any pending unlock requests for this class/term — grades are re-submitted,
        // so the unlock is no longer needed (spec: grade-edit-hub-consistency.md).
        $cancelled = \App\Models\GradeUnlockRequest::where('class_id', $class->id)
            ->where('grading_period', $data['grading_period'])
            ->where('status', \App\Models\GradeUnlockRequest::STATUS_PENDING)
            ->update(['status' => 'cancelled', 'reviewed_at' => now()]);

        // Principal + Registrar are notified (IT is out of the academic loop).
        $recipients = User::whereIn('role_id', [2, 9])->pluck('email')->filter();
        try {
            $submissionMarker = $request->input('_idempotency_key');
            foreach ($recipients as $index => $email) {
                $gradesMail = new GradesSubmittedMail($class, $data['grading_period']);
                // Per-recipient suffix: one submission notifies several people and each
                // notice is its own intent. Deterministic UUID — never personal data.
                $gradesMail->idempotencyMarker = is_string($submissionMarker) && $submissionMarker !== ''
                    ? \App\Models\IdempotencyKey::referenceFor($submissionMarker, (string) $index)
                    : null;
                Mail::to($email)->send($gradesMail);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Grades-submitted mail failed: ' . $e->getMessage(), ['class_id' => $class->id]);
        }

        log_activity($class, 'Grades Submitted', auth()->user()->name . ' submitted grades for ' . $data['grading_period'] . ' (' . $class->subject->name . ' - ' . $class->grade_level . ' ' . $class->section . '). ' . $gradeCount . ' grade(s) submitted.');

        return back()->with('success', 'Grades submitted for ' . $data['grading_period'] . '. ' . $gradeCount . ' grade(s) submitted.');
    }

    /**
     * Daily attendance per class (role reform Phase 4a — did not exist before).
     */
    public function attendance(Request $request, Classes $class)
    {
        if ($class->teacher_id !== auth()->id()) {
            abort(403);
        }

        $class->load('subject', 'enrollments.student');

        $activeEnrollments = $class->enrollments->filter(fn($e) => $e->status === 'Active')->values();

        $markedOn = $request->input('date', now()->toDateString());

        $existing = Attendance::where('class_id', $class->id)
            ->where('marked_on', $markedOn)
            ->get()
            ->keyBy('enrollment_id');

        // Recent history for context (last 5 marked days).
        $recentDates = Attendance::where('class_id', $class->id)
            ->orderByDesc('marked_on')
            ->distinct()
            ->pluck('marked_on')
            ->take(5);

        return view('portal.teacher.attendance', compact('class', 'activeEnrollments', 'markedOn', 'existing', 'recentDates'));
    }

    public function storeAttendance(Request $request, Classes $class)
    {
        if ($class->teacher_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'marked_on' => 'required|date|after_or_equal:1987-01-01|before_or_equal:today',
            'status' => 'required|array',
            'status.*' => 'required|in:present,absent,late,excused',
        ], [
            'marked_on.after_or_equal' => 'Attendance date is too far back.',
            'marked_on.before_or_equal' => "Attendance date can't be in the future.",
        ]);

        // Locked school years are frozen — attendance can no longer be changed.
        if (school_year_locked($class->school_year)) {
            return back()->with('error', 'School year ' . $class->school_year . ' is locked — attendance can no longer be changed.');
        }

        $validIds = $class->enrollments()->where('status', 'Active')->pluck('enrollments.id')->all();
        $count = 0;

        foreach ($data['status'] as $enrollmentId => $status) {
            if (!in_array((int) $enrollmentId, $validIds, true)) {
                continue;
            }
            Attendance::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'enrollment_id' => $enrollmentId,
                    'marked_on' => $data['marked_on'],
                ],
                ['status' => $status, 'marked_by' => auth()->id()]
            );
            $count++;
        }

        log_activity($class, 'Attendance Marked', auth()->user()->name . ' marked attendance for ' . $class->subject->name . ' (' . $class->grade_level . ' ' . $class->section . ') on ' . $data['marked_on'] . ' — ' . $count . ' student(s).');

        return redirect()->route('teacher.attendance', ['class' => $class->id, 'date' => $data['marked_on']])
            ->with('success', 'Attendance saved for ' . $data['marked_on'] . ' (' . $count . ' student(s)).');
    }

    public function assessments(Classes $class)
    {
        if ($class->teacher_id !== auth()->id()) {
            return redirect()->route('teacher.grade-assessment')->with('error', 'Not your class — you can only open your own classes.');
        }

        $class->load('subject', 'schedules', 'enrollments.student');

        $activeEnrollments = $class->enrollments->filter(function ($e) {
            return $e->status === 'Active';
        });

        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];
        $selectedPeriod = request('grading_period', $gradingPeriods[0]);

        $assessmentTypes = Assessment::ASSESSMENT_TYPES;

        $existingAssessments = Assessment::where('class_id', $class->id)
            ->where('grading_period', $selectedPeriod)
            ->orderByDesc('id')
            ->get()
            ->groupBy('enrollment_id');

        $resolvedWeights = app(\App\Services\GradeWeightService::class)->forClass($class->subject, $class->grade_level);

        $schoolYear = $class->school_year;
        $schoolYears = all_school_years();
        if (!empty($schoolYear) && !$schoolYears->contains($schoolYear)) {
            $schoolYears = $schoolYears->push($schoolYear)->sortDesc()->values();
        }
        $schoolYearLocked = school_year_locked($class->school_year);

        // Stale bookmark or picker context from another year — leave to the
        // year-filtered list with a plain note instead of opening silently.
        $requestedYear = request('school_year');
        if (!empty($requestedYear) && !empty($schoolYear) && $requestedYear !== $schoolYear) {
            return redirect()->route('teacher.grade-assessment', ['school_year' => $requestedYear, 'grading_period' => $selectedPeriod])->with('error', 'Wrong year — this class belongs to S.Y. ' . $schoolYear . '. Showing S.Y. ' . $requestedYear . ' list.');
        }

        return view('portal.teacher.assessments', compact('class', 'activeEnrollments', 'gradingPeriods', 'selectedPeriod', 'assessmentTypes', 'existingAssessments', 'resolvedWeights', 'schoolYear', 'schoolYears', 'schoolYearLocked'));
    }

    public function storeAssessments(Request $request, Classes $class)
    {
        if ($class->teacher_id !== auth()->id()) {
            return redirect()->route('teacher.grade-assessment')->with('error', 'Not your class — you can only save your own classes.');
        }

        $data = $request->validate([
            'grading_period' => 'required|string|in:1st Term,2nd Term,3rd Term',
            'rows' => 'required|array|min:1',
            'rows.*.enrollment_id' => 'nullable|integer',
            'rows.*.type' => ['nullable', 'string', 'in:' . implode(',', Assessment::ASSESSMENT_TYPES)],
            'rows.*.title' => 'nullable|string|max:255',
            'rows.*.assessment_date' => 'nullable|date|before_or_equal:today',
            'rows.*.raw_score' => 'nullable|numeric|min:0',
            'rows.*.max_score' => 'nullable|numeric|min:0',
            'rows.*.remarks' => 'nullable|string|max:1000',
        ], [
            'rows.*.assessment_date.before_or_equal' => 'An assessment date is in the future — use today or earlier.',
        ]);

        // Locked school years are frozen — assessments can no longer be changed.
        if (school_year_locked($class->school_year)) {
            return back()->with('error', 'School year ' . $class->school_year . ' is locked — assessments can no longer be changed.');
        }

        // Year-picker context must never re-point a save into another year.
        $expectedYear = $request->input('school_year', $class->school_year);
        if (!empty($expectedYear) && $expectedYear !== $class->school_year) {
            return back()->withInput()->with('error', 'Wrong year — this class belongs to S.Y. ' . $class->school_year . '. Go back to the list and pick S.Y. ' . $expectedYear . ' there.');
        }

        // Submitted finals lock their assessments too — only an approved
        // unlock reopens them. Never wipe locked students' assessments.
        $lockedIds = Grade::where('class_id', $class->id)
            ->where('grading_period', $data['grading_period'])
            ->where('status', 'Submitted')
            ->pluck('enrollment_id')
            ->flip()
            ->toArray();

        $activeIds = $class->enrollments->filter(fn($e) => $e->status === 'Active')->pluck('id')->flip()->toArray();

        $enrollmentYears = Enrollment::whereIn('id', array_keys($activeIds))->pluck('school_year', 'id')->toArray();

// Row-level semantic checks with row numbers — failed saves re-render
// every entered row from the submitted input (never wiped).
$rowErrors = [];
$kept = [];
foreach ($data['rows'] as $idx => $row) {
    $rowNo = $idx + 1;
    $hasContent = !empty($row['enrollment_id']) || !empty($row['raw_score']) || !empty($row['max_score']) || !empty($row['remarks']) || !empty($row['title']);
    if (!$hasContent) {
        continue;
    }
    if (empty($row['enrollment_id']) || !isset($activeIds[$row['enrollment_id']])) {
        $rowErrors[] = "Row {$rowNo}: pick a student from this class.";
        continue;
    }
    $studentYear = $enrollmentYears[$row['enrollment_id']] ?? $class->school_year;
    $classYear = $class->school_year;
    if ($studentYear !== $classYear) {
        $rowErrors[] = "Row {$rowNo}: wrong year — this student belongs to S.Y. {$studentYear}. Go back to the list.";
        continue;
    }
    if (($row['raw_score'] === null || $row['raw_score'] === '') && ($row['max_score'] === null || $row['max_score'] === '') && empty($row['remarks']) && empty($row['title'])) {
        continue;
    }
    if ($row['raw_score'] !== null && $row['raw_score'] !== '' && ($row['max_score'] === null || $row['max_score'] === '' || (float) $row['raw_score'] > (float) $row['max_score'])) {
        $rowErrors[] = "Row {$rowNo}: raw score exceeds its max score.";
        continue;
    }
            $kept[] = $row;
        }

        if (!empty($rowErrors)) {
            return back()->withErrors(['rows' => $rowErrors])->withInput();
        }

        if (empty($kept)) {
            return back()->with('error', 'Nothing to save — add a row with a student and scores first.');
        }

        $inserts = [];
        $skipped = 0;
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($class, $data, $kept, $lockedIds, &$inserts, &$skipped) {
                Assessment::where('class_id', $class->id)
                    ->where('grading_period', $data['grading_period'])
                    ->when(!empty($lockedIds), fn($q) => $q->whereNotIn('enrollment_id', array_keys($lockedIds)))
                    ->delete();

                foreach ($kept as $row) {
                    if (isset($lockedIds[$row['enrollment_id']])) {
                        $skipped++;
                        continue;
                    }
                    $inserts[] = [
                        'enrollment_id' => $row['enrollment_id'],
                        'class_id' => $class->id,
                        'type' => $row['type'] ?? Assessment::ASSESSMENT_TYPES[0],
                        'title' => $row['title'] ?? '',
                        'raw_score' => $row['raw_score'] ?? 0,
                        'max_score' => $row['max_score'] ?? 0,
                        'assessment_date' => !empty($row['assessment_date']) ? $row['assessment_date'] : null,
                        'remarks' => $row['remarks'] ?? null,
                        'grading_period' => $data['grading_period'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($inserts)) {
                    Assessment::insert($inserts);
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Assessments save failed: ' . $e->getMessage(), ['class_id' => $class->id]);
            return back()->withInput()->with('error', 'Could not save — please refresh and try again.');
        }

        try {
            log_activity($class, 'Assessments Saved', auth()->user()->name . ' saved assessments for ' . $data['grading_period'] . ' (' . ($class->subject?->name ?? 'class') . ' - ' . ($class->grade_level ?? '') . ' ' . ($class->section ?? '') . '). ' . count($inserts) . ' assessment(s) recorded.' . ($skipped ? " {$skipped} submitted (locked) student(s) skipped." : ''));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Assessments activity log failed: ' . $e->getMessage(), ['class_id' => $class->id]);
        }

        $saveAction = $request->input('save_action', 'save');
        $message = $saveAction === 'draft'
            ? 'Draft saved for ' . $data['grading_period'] . ' — still editable.'
            : 'Assessments saved for ' . $data['grading_period'] . '. Review Computed & Post to lock.';
        if ($skipped) {
            $message .= " {$skipped} submitted student(s) were skipped (locked) — request a correction unlock to change them.";
        }

        return back()->with('success', $message);
    }

    public function schedule(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $teacherId = auth()->id();
        $schoolYear = $request->input('school_year', active_school_year());
        $schoolYears = all_school_years();
        $classes = Classes::with('subject', 'schedules')
            ->where('teacher_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->where('status', 'active')
            ->get();

        $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        $allSchedules = Schedule::whereHas('schoolClass', function ($q) use ($teacherId, $schoolYear) {
            $q->where('teacher_id', $teacherId)
                ->where('school_year', $schoolYear)
                ->where('status', 'active');
        })
        ->with('schoolClass.subject')
        ->orderBy('start_time')
        ->get()
        ->groupBy('day_of_week');

        $schedulesByDay = [];
        foreach ($weekDays as $day) {
            $schedulesByDay[$day] = $allSchedules->get($day, collect());
        }

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.teacher.partials.schedule-results', compact('weekDays', 'schedulesByDay', 'schoolYear', 'schoolYears'))->render(),
            ]);
        }

        return view('portal.teacher.schedule', compact('classes', 'weekDays', 'schedulesByDay', 'schoolYear', 'schoolYears'));
    }

    // ─── NEW SUB-TAB: List of Classes (Master List) ─────────────────────

    public function classList(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $teacherId = auth()->id();
        $schoolYear = $request->input('school_year', active_school_year());
        $schoolYears = all_school_years();
        $query = Classes::with('subject')
            ->where('teacher_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('subject', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('subject_code', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        $classes = $query->get();
        $gradeLevels = $classes->pluck('grade_level')->unique()->sort()->values()->all();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.teacher.partials.class-list-results', compact('classes', 'gradeLevels', 'schoolYear', 'schoolYears'))->render(),
            ]);
        }

        return view('portal.teacher.class-list', compact('classes', 'gradeLevels', 'schoolYear', 'schoolYears'));
    }

    public function classStudents(Request $request, Classes $class)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        if ($class->teacher_id !== auth()->id()) {
            abort(403);
        }

        $class->load('subject', 'enrollments.student.user');

        $activeEnrollments = $class->enrollments->filter(function ($e) {
            return $e->status === 'Active';
        });

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.teacher.partials.class-students-results', compact('class', 'activeEnrollments'))->render(),
            ]);
        }

        return view('portal.teacher.class-students', compact('class', 'activeEnrollments'));
    }

    // ─── NEW SUB-TAB: Grade Assessment ──────────────────────────────────

    public function gradeAssessment(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $teacherId = auth()->id();
        $selectedPeriod = request('grading_period', '1st Term');
        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];
        $assessmentTypes = Assessment::ASSESSMENT_TYPES;
        $schoolYear = $request->input('school_year', active_school_year());
        $schoolYears = all_school_years();

        $classes = Classes::with('subject')
            ->where('teacher_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->where('status', 'active')
            ->where(function($q) use ($selectedPeriod) { $q->where('term', $selectedPeriod)->orWhereNull('term')->orWhere('term',''); })
            ->when(request('grade_level'), fn($q) => $q->where('grade_level', request('grade_level')))
            ->when(request('section'), fn($q) => $q->where('section', request('section')))
            ->get();

        // Alphabetical picker (spec Amendment A): subject → grade → section.
        $classes = $classes->sortBy(fn($c) => sprintf(
            '%s|%s|%s',
            mb_strtolower((string) ($c->subject->name ?? '')),
            mb_strtolower((string) $c->grade_level),
            mb_strtolower((string) $c->section)
        ))->values();

        $pickerGrades = Classes::where('teacher_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->where('status', 'active')
            ->distinct()->orderBy('grade_level')->pluck('grade_level');
        $pickerSections = Classes::where('teacher_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->where('status', 'active')
            ->distinct()->orderBy('section')->pluck('section');

        $selectedClassId = request('class_id');

        $class = null;
        $activeEnrollments = collect();
        $existingAssessments = collect();

        if ($selectedClassId) {
            $class = Classes::with('subject')->find($selectedClassId);
            if ($class && $class->teacher_id === auth()->id()) {
                $class->load('enrollments.student');
                $activeEnrollments = $class->enrollments->filter(fn($e) => $e->status === 'Active');
                $existingAssessments = Assessment::where('class_id', $class->id)
                    ->where('grading_period', $selectedPeriod)
                    ->get()
                    ->groupBy('enrollment_id');
            } else {
                $class = null;
            }
        }

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.teacher.partials.grade-assessment-results', compact(
                    'classes', 'class', 'activeEnrollments', 'existingAssessments',
                    'gradingPeriods', 'selectedPeriod', 'selectedClassId', 'assessmentTypes', 'schoolYear', 'schoolYears', 'pickerGrades', 'pickerSections'
                ))->render(),
            ]);
        }

        return view('portal.teacher.grade-assessment', compact(
            'classes', 'class', 'activeEnrollments', 'existingAssessments',
            'gradingPeriods', 'selectedPeriod', 'selectedClassId', 'assessmentTypes', 'schoolYear', 'schoolYears', 'pickerGrades', 'pickerSections'
        ));
    }

    public function gradeAssessmentStudent(Classes $class, $enrollmentId)
    {
        if ($class->teacher_id !== auth()->id()) {
            return redirect()->route('teacher.grade-assessment')->with('error', 'Not your class — you can only open your own classes.');
        }

        $class->load('subject');
        $enrollment = Enrollment::with('student')->find($enrollmentId);
        if ($enrollment === null || $enrollment->student === null) {
            return redirect()->route('teacher.grade-assessment', ['class_id' => $class->id])->with('error', 'Could not open — please refresh and try again.');
        }
        if (!$class->enrollments()->where('enrollments.id', $enrollmentId)->exists()) {
            return redirect()->route('teacher.grade-assessment', ['class_id' => $class->id])->with('error', 'Could not open — this student is no longer in this class. Please refresh.');
        }
        if (!empty($enrollment->school_year) && !empty($class->school_year) && $enrollment->school_year !== $class->school_year) {
            return redirect()->route('teacher.grade-assessment', ['class_id' => $class->id])->with('error', 'Wrong year — this student belongs to S.Y. ' . $enrollment->school_year . '. Go back to the list.');
        }
        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];
        $selectedPeriod = request('grading_period', '1st Term');
        $assessmentTypes = Assessment::ASSESSMENT_TYPES;

        $existingAssessments = Assessment::where('class_id', $class->id)
            ->where('enrollment_id', $enrollmentId)
            ->where('grading_period', $selectedPeriod)
            ->get()
            ->groupBy('type');

        $schoolYear = $class->school_year;
        $schoolYears = all_school_years();
        if (!empty($schoolYear) && !$schoolYears->contains($schoolYear)) {
            $schoolYears = $schoolYears->push($schoolYear)->sortDesc()->values();
        }
        $schoolYearLocked = school_year_locked($class->school_year);

        // Stale bookmark or picker context from another year — leave to the
        // year-filtered list with a plain note instead of opening silently.
        $requestedYear = request('school_year');
        if (!empty($requestedYear) && !empty($schoolYear) && $requestedYear !== $schoolYear) {
            return redirect()->route('teacher.grade-assessment', ['school_year' => $requestedYear, 'grading_period' => $selectedPeriod])->with('error', 'Wrong year — this class belongs to S.Y. ' . $schoolYear . '. Showing S.Y. ' . $requestedYear . ' list.');
        }

        return view('portal.teacher.grade-assessment-student', compact(
            'class', 'enrollment', 'gradingPeriods', 'selectedPeriod', 'assessmentTypes', 'existingAssessments', 'schoolYear', 'schoolYears', 'schoolYearLocked'
        ));
    }

    public function storeGradeAssessmentStudent(StoreGradeAssessmentStudentRequest $request, Classes $class, $enrollmentId): \Illuminate\Http\RedirectResponse
    {
        if ($class->teacher_id !== auth()->id()) {
            return redirect()->route('teacher.grade-assessment')->with('error', 'Not your class — you can only save your own classes.');
        }

        // Locked school years are frozen — assessments can no longer be changed.
        if (school_year_locked($class->school_year)) {
            return back()->withInput()->with('error', 'School year ' . $class->school_year . ' is locked — assessments can no longer be changed.');
        }

        // Year-picker context must never re-point a save into another year.
        $expectedYear = $request->input('school_year', $class->school_year);
        if (!empty($expectedYear) && !empty($class->school_year) && $expectedYear !== $class->school_year) {
            return back()->withInput()->with('error', 'Wrong year — this class belongs to S.Y. ' . $class->school_year . '. Go back to the list and pick S.Y. ' . $expectedYear . ' there.');
        }

        $data = $request->validated();

        // Missing class/student records stay on the sheet — never a 500 page.
        $enrollment = Enrollment::find($enrollmentId);
        if ($enrollment === null || $enrollment->student === null) {
            return back()->withInput()->with('error', 'Could not save — please refresh and try again.');
        }
        $belongsToClass = $class->enrollments()->where('enrollments.id', $enrollmentId)->exists();
        if (!$belongsToClass) {
            return back()->withInput()->with('error', 'Could not save — this student is no longer in this class. Please refresh and try again.');
        }
        // Year check: always compare, using class year as default if either side is missing from DB
        $enrollmentYear = $enrollment->school_year ?? $class->school_year;
        $classYear = $class->school_year;
        if ($enrollmentYear !== $classYear) {
            return back()->withInput()->with('error', 'Wrong year — this student belongs to S.Y. ' . ($enrollmentYear ?? $classYear) . '. Go back to the list.');
        }

        // Same-type repeats (Quiz 1 + Quiz 2) save as separate rows —
        // spec grade-assessment-save-post-500-fix.md §6 (approved 2026-10-12).
        // The unique key is widened to include title + date (migration).

        // A submitted final grade locks this student's assessments too.
        $locked = Grade::where('class_id', $class->id)
            ->where('enrollment_id', $enrollmentId)
            ->where('grading_period', $data['grading_period'])
            ->where('status', 'Submitted')
            ->exists();
        if ($locked) {
            return back()->withInput()->with('error', 'These assessments are locked (final grade already submitted) — request a correction unlock to change them.');
        }

        // Row-level semantic checks with row numbers — failed saves re-render
        // every entered row from the submitted input (never wiped).
        $rowErrors = [];
        $inserts = [];
        foreach (array_values($data['assessments']) as $idx => $item) {
            $rowNo = $idx + 1;
            if (empty($item['title']) && ($item['raw_score'] === null || $item['raw_score'] === '') && empty($item['remarks']) && empty($item['assessment_date'])) {
                continue;
            }
            $raw = $item['raw_score'] ?? null;
            $max = $item['max_score'] ?? null;
            if ($raw !== null && $raw !== '' && $max !== null && $max !== '' && (float) $raw > (float) $max) {
                $rowErrors[] = "Row {$rowNo}: score above max ({$raw}/{$max}).";
                continue;
            }
            if (!empty($item['assessment_date']) && $item['assessment_date'] > date('Y-m-d')) {
                $rowErrors[] = "Row {$rowNo}: date is in the future — use today or earlier.";
                continue;
            }
            $inserts[] = [
                'enrollment_id' => $enrollmentId,
                'class_id' => $class->id,
                'type' => $item['type'],
                'title' => $item['title'] ?? '',
                'raw_score' => ($raw === null || $raw === '') ? 0 : $raw,
                'max_score' => ($max === null || $max === '') ? 0 : $max,
                'assessment_date' => !empty($item['assessment_date']) ? $item['assessment_date'] : null,
                'remarks' => $item['remarks'] ?? null,
                'grading_period' => $data['grading_period'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($rowErrors)) {
            return back()->withErrors(['assessments' => $rowErrors])->withInput();
        }

        if (empty($inserts)) {
            return back()->withInput()->with('error', 'Nothing to save — add a score first.');
        }

        // Whole student or nothing — delete + insert stay in one transaction
        // so a failure never leaves a half-saved student. Any database
        // hiccup stays on the sheet — never a 500 page.
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($class, $enrollmentId, $data, $inserts) {
                Assessment::where('class_id', $class->id)
                    ->where('enrollment_id', $enrollmentId)
                    ->where('grading_period', $data['grading_period'])
                    ->delete();

                Assessment::insert($inserts);
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Student assessments save failed: ' . $e->getMessage(), ['class_id' => $class->id, 'enrollment_id' => $enrollmentId]);
            return back()->withInput()->with('error', 'Could not save — please refresh and try again.');
        }

        $subjectName = $class->subject?->name ?? 'class';
        try {
            log_activity($class, 'Student Assessments Saved', auth()->user()->name . ' saved assessments for student #' . $enrollmentId . ' in ' . $data['grading_period'] . ' (' . $subjectName . '). ' . count($inserts) . ' assessment(s) recorded.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Student-assessments activity log failed: ' . $e->getMessage(), ['class_id' => $class->id]);
        }

        return back()->with('success', 'Saved ' . count($inserts) . ' row(s) for this student.');
    }

    // ─── NEW SUB-TAB: Computed Grades ───────────────────────────────────

    public function computedGrades(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $teacherId = auth()->id();
        $selectedPeriod = request('grading_period', '1st Term');
        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];
        $schoolYear = $request->input('school_year', active_school_year());
        $schoolYears = all_school_years();

        $classes = Classes::with('subject')
            ->where('teacher_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->where('status', 'active')
            ->where(function($q) use ($selectedPeriod) { $q->where('term', $selectedPeriod)->orWhereNull('term')->orWhere('term',''); })
            ->when(request('grade_level'), fn($q) => $q->where('grade_level', request('grade_level')))
            ->when(request('section'), fn($q) => $q->where('section', request('section')))
            ->get();

        // Alphabetical picker (spec Amendment A): subject → grade → section.
        $classes = $classes->sortBy(fn($c) => sprintf(
            '%s|%s|%s',
            mb_strtolower((string) ($c->subject->name ?? '')),
            mb_strtolower((string) $c->grade_level),
            mb_strtolower((string) $c->section)
        ))->values();

        $pickerGrades = Classes::where('teacher_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->where('status', 'active')
            ->distinct()->orderBy('grade_level')->pluck('grade_level');
        $pickerSections = Classes::where('teacher_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->where('status', 'active')
            ->distinct()->orderBy('section')->pluck('section');

        $selectedClassId = request('class_id');

        $class = null;
        $computedGrades = collect();
        $resolvedWeights = null;

        if ($selectedClassId) {
            $class = Classes::with('subject')->find($selectedClassId);
            if ($class && $class->teacher_id === auth()->id()) {
                $class->load('enrollments.student');
                $activeEnrollments = $class->enrollments->filter(fn($e) => $e->status === 'Active');

                $assessmentTypes = Assessment::ASSESSMENT_TYPES;
                // One shared MATATAG weights row per class (spec: teacher-encode-grades-deped-layout.md §6).
                // Legacy rows fold in: Quiz, Seatwork and singular Written Work
                // count as Written Works; Exam counts as Quarterly Assessment.
                // Empty bucket (no max) shows `–` and adds nothing; QA-None rows
                // (QA weight 0) skip QA math; no QA inner split per teacher call.
                $resolvedWeights = app(\App\Services\GradeWeightService::class)->forClass($class->subject, $class->grade_level);
                $weights = [
                    'Written Works' => $resolvedWeights['written_works'] / 100,
                    'Performance Tasks' => $resolvedWeights['performance_tasks'] / 100,
                    'Quarterly Assessment' => $resolvedWeights['quarterly_assessment'] / 100,
                ];
                $transmutation = app(\App\Services\TransmutationService::class);
                $legacyTypeMap = [
                    'Written Work' => 'Written Works',
                    'Quiz' => 'Written Works',
                    'Seatwork' => 'Written Works',
                    'Exam' => 'Quarterly Assessment',
                ];

                $allAssessments = Assessment::where('class_id', $class->id)
                    ->where('grading_period', $selectedPeriod)
                    ->get()
                    ->groupBy('enrollment_id');

                $allGrades = Grade::where('class_id', $class->id)
                    ->where('grading_period', $selectedPeriod)
                    ->get()
                    ->keyBy('enrollment_id');

                $computedGrades = $activeEnrollments->map(function ($enrollment) use ($class, $selectedPeriod, $assessmentTypes, $weights, $resolvedWeights, $transmutation, $legacyTypeMap, $allAssessments, $allGrades) {
                    $assessments = $allAssessments->get($enrollment->id, collect());

                    $categoryScores = [];
                    foreach ($assessmentTypes as $type) {
                        $typeAssessments = $assessments->filter(fn($a) => ($legacyTypeMap[$a->type] ?? $a->type) === $type);
                        $totalRaw = (float) $typeAssessments->sum('raw_score');
                        $totalMax = (float) $typeAssessments->sum('max_score');
                        $worksCount = $typeAssessments->count();
                        $isQaSkipped = $type === 'Quarterly Assessment' && ($resolvedWeights['quarterly_assessment'] ?? 0) <= 0;
                        // Raw normalizes against its own total (e.g. 10/10 = 100%, 8/10 = 80%).
                        // Empty (no max) or skipped QA stays null → renders `–`, adds nothing.
                        $percentage = ($totalMax > 0 && !$isQaSkipped) ? round(($totalRaw / $totalMax) * 100, 2) : null;
                        $weighted = $percentage !== null ? round($percentage * ($weights[$type] ?? 0), 2) : 0.0;
                        $categoryScores[$type] = [
                            'count' => $worksCount,
                            'raw' => $totalRaw,
                            'max' => $totalMax,
                            'percentage' => $percentage,
                            'weighted' => $weighted,
                        ];
                    }

                    $hasAnyScore = collect($categoryScores)->contains(fn($scores) => $scores['percentage'] !== null);
                    $initialGrade = $hasAnyScore ? round(collect($categoryScores)->sum('weighted'), 2) : null;
                    $quarterlyGrade = $initialGrade !== null ? $transmutation->transmute((float) $initialGrade) : null;

                    $existingGrade = $allGrades->get($enrollment->id);

                    return [
                        'enrollment_id' => $enrollment->id,
                        'student' => $enrollment->student,
                        'categories' => $categoryScores,
                        'initial_grade' => $initialGrade,
                        'quarterly_grade' => $quarterlyGrade,
                        'computed_grade' => $initialGrade,
                        'final_grade' => $existingGrade?->final_grade,
                        'status' => $existingGrade?->status,
                    ];
                });
            } else {
                $class = null;
            }
        }

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.teacher.partials.computed-grades-results', compact(
                    'classes', 'class', 'computedGrades', 'gradingPeriods', 'selectedPeriod', 'selectedClassId', 'schoolYear', 'schoolYears', 'pickerGrades', 'pickerSections', 'resolvedWeights'
                ))->render(),
            ]);
        }

        return view('portal.teacher.computed-grades', compact(
            'classes', 'class', 'computedGrades', 'gradingPeriods', 'selectedPeriod', 'selectedClassId', 'schoolYear', 'schoolYears', 'pickerGrades', 'pickerSections', 'resolvedWeights'
        ));
    }

    public function batchSubmitGrades(\App\Http\Requests\Portal\BatchGradeRequest $request)
    {
        $data = $request->validated();
        // Option A split: Encode builds (draft/save), Computed locks (post).
        // Draft keeps everything editable, Save stores, Post locks like Submitted.
        $action = $data['action'] ?? 'save';
        $targetStatus = $action === 'draft' ? 'Draft' : ($action === 'post' ? 'Submitted' : 'Pending');

        $class = Classes::find($data['class_id']);
        if ($class === null) {
            return back()->withInput()->with('error', 'Could not save — please refresh and try again.');
        }
        if ($class->teacher_id !== auth()->id()) {
            return back()->with('error', 'Not your class — you can only post your own classes.');
        }

        // Locked school years are frozen — grades can no longer be changed.
        if (school_year_locked($class->school_year)) {
            return back()->with('error', 'School year ' . $class->school_year . ' is locked — grades can no longer be changed.');
        }

        // Only students still in this class may save — stale rows stay on the
        // sheet with a plain note instead of a 500 page.
        $validIds = $class->enrollments()->pluck('enrollments.id')->flip()->toArray();
        $validGrades = [];
        $stale = 0;
        foreach ($data['grades'] as $gradeData) {
            if (!isset($validIds[$gradeData['enrollment_id']])) {
                $stale++;
                continue;
            }
            $validGrades[] = $gradeData;
        }

        if (empty($validGrades)) {
            return back()->withInput()->with('error', 'Nothing to save — please refresh and try again.' . ($stale ? " {$stale} row(s) no longer belong to this class." : ''));
        }

        // Submitted grades are locked — changes need the unlock-request approval.
        $lockedIds = Grade::where('class_id', $class->id)
            ->where('grading_period', $data['grading_period'])
            ->where('status', 'Submitted')
            ->pluck('enrollment_id')
            ->flip();

        $skipped = 0;
        $saved = 0;
        $skippedNames = [];
        // Whole class or nothing — never a half-saved class. updateOrCreate is
        // idempotent so double-tapping Draft/Save/Post never duplicates rows.
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($validGrades, $class, $data, $targetStatus, $lockedIds, &$skipped, &$saved) {
                foreach ($validGrades as $gradeData) {
                    if (isset($lockedIds[$gradeData['enrollment_id']])) {
                        $skipped++;
                        continue;
                    }
                    Grade::updateOrCreate(
                        [
                            'enrollment_id' => $gradeData['enrollment_id'],
                            'class_id' => $class->id,
                            'grading_period' => $data['grading_period'],
                        ],
                        [
                            'final_grade' => $gradeData['final_grade'],
                            'status' => $targetStatus,
                        ]
                    );
                    $saved++;
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Batch grades save failed: ' . $e->getMessage(), ['class_id' => $class->id]);
            return back()->withInput()->with('error', 'Could not save — please refresh and try again.');
        }

        if ($skipped > 0) {
            $lockedInPayload = collect($validGrades)->pluck('enrollment_id')->filter(fn($id) => isset($lockedIds[$id]))->unique()->values()->all();
            $skippedNames = Enrollment::with('student')->whereIn('id', $lockedInPayload)->get()->map(fn($e) => trim(($e->student->first_name ?? '') . ' ' . ($e->student->last_name ?? '')) ?: ('#' . $e->id))->values()->all();
        }

        $pastTense = $action === 'draft' ? 'draft-saved' : ($action === 'post' ? 'posted' : 'saved');
        $subjectName = $class->subject?->name ?? 'class';
        $classLabel = $subjectName . ' - ' . ($class->grade_level ?? '') . ' ' . ($class->section ?? '');
        try {
            log_activity($class, 'Batch Grades ' . $pastTense, auth()->user()->name . ' batch-' . $pastTense . ' grades for ' . $data['grading_period'] . ' (' . $classLabel . '). ' . $saved . ' grade(s) ' . $pastTense . '.' . ($skipped ? " {$skipped} submitted (locked) grade(s) skipped." : ''));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Batch-grades activity log failed: ' . $e->getMessage(), ['class_id' => $class->id]);
        }

        $message = $saved . ' grade(s) ' . $pastTense . ' for ' . $data['grading_period'];
        $message .= $action === 'draft' ? ' (Draft — still editable).' : ($action === 'post' ? ' (Posted — locked; request a correction unlock to change).' : ' (Saved).');
        if ($skipped) {
            $shown = array_slice($skippedNames, 0, 5);
            $extra = count($skippedNames) - count($shown);
            $message .= " {$skipped} submitted grade(s) were skipped (locked) — request a correction unlock to change them.";
            if (!empty($shown)) {
                $message .= ' Skipped: ' . implode(', ', $shown) . ($extra > 0 ? " and {$extra} more." : '.');
            }
        }
        if ($stale) {
            $message .= " {$stale} row(s) no longer belong to this class — refreshed list shown.";
        }

        return back()->with('success', $message);
    }
}
