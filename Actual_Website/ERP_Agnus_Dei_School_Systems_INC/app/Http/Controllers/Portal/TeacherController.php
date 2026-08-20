<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
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
        $classes = Classes::with('subject', 'schedules', 'enrollments')
            ->where('teacher_id', $teacherId)
            ->where('school_year', active_school_year())
            ->where('status', 'active')
            ->get();

        $today = now()->format('l');
        $todaySchedule = $classes->flatMap->schedules->filter(function ($s) use ($today) {
            return $s->day_of_week === $today;
        })->sortBy('start_time');

        $totalStudents = $classes->sum(function ($c) {
            return $c->enrollments->where('status', 'Active')->count();
        });

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.teacher.partials.dashboard-results', compact('classes', 'todaySchedule', 'totalStudents'))->render(),
            ]);
        }

        return view('portal.teacher.dashboard', compact('classes', 'todaySchedule', 'totalStudents'));
    }

    public function classes(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $teacherId = auth()->id();
        $query = Classes::with('subject', 'schedules', 'teacher')
            ->where('teacher_id', $teacherId)
            ->where('school_year', active_school_year())
            ->where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('subject', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        $classes = $query->get();
        $gradeLevels = $classes->pluck('grade_level')->unique()->sort()->values()->all();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.teacher.partials.classes-results', compact('classes', 'gradeLevels'))->render(),
            ]);
        }

        return view('portal.teacher.classes', compact('classes', 'gradeLevels'));
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

        return view('portal.teacher.grades', compact('class', 'activeEnrollments', 'gradingPeriods', 'selectedPeriod', 'existingGrades'));
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

        foreach ($data['grades'] as $enrollmentId => $finalGrade) {
            if ($finalGrade === null || $finalGrade === '') {
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

        return back()->with('success', 'Grades saved for ' . $data['grading_period'] . '.');
    }

    public function submitGrades(Request $request, Classes $class)
    {
        if ($class->teacher_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'grading_period' => 'required|string|in:1st Term,2nd Term,3rd Term',
        ]);

        $gradeCount = Grade::where('class_id', $class->id)
            ->where('grading_period', $data['grading_period'])
            ->where('status', 'Pending')
            ->update(['status' => 'Submitted']);

        $recipients = User::whereIn('role_id', [1, 2])->pluck('email')->filter();
        foreach ($recipients as $email) {
            Mail::to($email)->send(new GradesSubmittedMail($class, $data['grading_period']));
        }

        return back()->with('success', 'Grades submitted for ' . $data['grading_period'] . '. ' . $gradeCount . ' grade(s) submitted.');
    }

    public function assessments(Classes $class)
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

        $assessmentTypes = ['Written Work', 'Quiz', 'Seatwork', 'Exam'];

        $existingAssessments = Assessment::where('class_id', $class->id)
            ->where('grading_period', $selectedPeriod)
            ->get()
            ->groupBy('enrollment_id');

        return view('portal.teacher.assessments', compact('class', 'activeEnrollments', 'gradingPeriods', 'selectedPeriod', 'assessmentTypes', 'existingAssessments'));
    }

    public function storeAssessments(Request $request, Classes $class)
    {
        if ($class->teacher_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'grading_period' => 'required|string|in:1st Term,2nd Term,3rd Term',
            'assessments' => 'required|array',
            'assessments.*' => 'required|array',
            'assessments.*.*.type' => 'required|string|in:Written Work,Quiz,Seatwork,Exam',
            'assessments.*.*.title' => 'nullable|string|max:255',
            'assessments.*.*.raw_score' => 'nullable|numeric|min:0',
            'assessments.*.*.max_score' => 'nullable|numeric|min:0',
        ]);

        Assessment::where('class_id', $class->id)
            ->where('grading_period', $data['grading_period'])
            ->delete();

        $inserts = [];
        foreach ($data['assessments'] as $enrollmentId => $items) {
            foreach ($items as $item) {
                if (empty($item['title']) && empty($item['raw_score'])) {
                    continue;
                }
                $inserts[] = [
                    'enrollment_id' => $enrollmentId,
                    'class_id' => $class->id,
                    'type' => $item['type'],
                    'title' => $item['title'] ?? '',
                    'raw_score' => $item['raw_score'] ?? 0,
                    'max_score' => $item['max_score'] ?? 0,
                    'grading_period' => $data['grading_period'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($inserts)) {
            Assessment::insert($inserts);
        }

        return back()->with('success', 'Assessments saved for ' . $data['grading_period'] . '.');
    }

    public function schedule(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $teacherId = auth()->id();
        $classes = Classes::with('subject', 'schedules')
            ->where('teacher_id', $teacherId)
            ->where('school_year', active_school_year())
            ->where('status', 'active')
            ->get();

        $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        $allSchedules = Schedule::whereHas('schoolClass', function ($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId)
                ->where('school_year', active_school_year())
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
                'html' => view('portal.teacher.partials.schedule-results', compact('weekDays', 'schedulesByDay'))->render(),
            ]);
        }

        return view('portal.teacher.schedule', compact('classes', 'weekDays', 'schedulesByDay'));
    }

    // ─── NEW SUB-TAB: List of Classes (Master List) ─────────────────────

    public function classList(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $teacherId = auth()->id();
        $query = Classes::with('subject')
            ->where('teacher_id', $teacherId)
            ->where('school_year', active_school_year())
            ->where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('subject', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        $classes = $query->get();
        $gradeLevels = $classes->pluck('grade_level')->unique()->sort()->values()->all();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.teacher.partials.class-list-results', compact('classes', 'gradeLevels'))->render(),
            ]);
        }

        return view('portal.teacher.class-list', compact('classes', 'gradeLevels'));
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
        $classes = Classes::with('subject')
            ->where('teacher_id', $teacherId)
            ->where('school_year', active_school_year())
            ->where('status', 'active')
            ->get();

        $selectedClassId = request('class_id');
        $selectedPeriod = request('grading_period', '1st Term');
        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];
        $assessmentTypes = ['Written Work', 'Quiz', 'Seatwork', 'Exam'];

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
                    'gradingPeriods', 'selectedPeriod', 'selectedClassId', 'assessmentTypes'
                ))->render(),
            ]);
        }

        return view('portal.teacher.grade-assessment', compact(
            'classes', 'class', 'activeEnrollments', 'existingAssessments',
            'gradingPeriods', 'selectedPeriod', 'selectedClassId', 'assessmentTypes'
        ));
    }

    public function gradeAssessmentStudent(Classes $class, $enrollmentId)
    {
        if ($class->teacher_id !== auth()->id()) {
            abort(403);
        }

        $class->load('subject');
        $enrollment = Enrollment::with('student')->findOrFail($enrollmentId);
        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];
        $selectedPeriod = request('grading_period', '1st Term');
        $assessmentTypes = ['Written Work', 'Quiz', 'Seatwork', 'Exam'];

        $existingAssessments = Assessment::where('class_id', $class->id)
            ->where('enrollment_id', $enrollmentId)
            ->where('grading_period', $selectedPeriod)
            ->get()
            ->groupBy('type');

        return view('portal.teacher.grade-assessment-student', compact(
            'class', 'enrollment', 'gradingPeriods', 'selectedPeriod', 'assessmentTypes', 'existingAssessments'
        ));
    }

    public function storeGradeAssessmentStudent(Request $request, Classes $class, $enrollmentId)
    {
        if ($class->teacher_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'grading_period' => 'required|string|in:1st Term,2nd Term,3rd Term',
            'assessments' => 'required|array',
            'assessments.*.type' => 'required|string|in:Written Work,Quiz,Seatwork,Exam',
            'assessments.*.title' => 'nullable|string|max:255',
            'assessments.*.raw_score' => 'nullable|numeric|min:0',
            'assessments.*.max_score' => 'nullable|numeric|min:0',
        ]);

        Assessment::where('class_id', $class->id)
            ->where('enrollment_id', $enrollmentId)
            ->where('grading_period', $data['grading_period'])
            ->delete();

        $inserts = [];
        foreach ($data['assessments'] as $item) {
            if (empty($item['title']) && empty($item['raw_score'])) {
                continue;
            }
            $inserts[] = [
                'enrollment_id' => $enrollmentId,
                'class_id' => $class->id,
                'type' => $item['type'],
                'title' => $item['title'] ?? '',
                'raw_score' => $item['raw_score'] ?? 0,
                'max_score' => $item['max_score'] ?? 0,
                'grading_period' => $data['grading_period'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($inserts)) {
            Assessment::insert($inserts);
        }

        return back()->with('success', 'Assessment scores saved for this student.');
    }

    // ─── NEW SUB-TAB: Computed Grades ───────────────────────────────────

    public function computedGrades(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $teacherId = auth()->id();
        $classes = Classes::with('subject')
            ->where('teacher_id', $teacherId)
            ->where('school_year', active_school_year())
            ->where('status', 'active')
            ->get();

        $selectedClassId = request('class_id');
        $selectedPeriod = request('grading_period', '1st Term');
        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];

        $class = null;
        $computedGrades = collect();

        if ($selectedClassId) {
            $class = Classes::with('subject')->find($selectedClassId);
            if ($class && $class->teacher_id === auth()->id()) {
                $class->load('enrollments.student');
                $activeEnrollments = $class->enrollments->filter(fn($e) => $e->status === 'Active');

                $assessmentTypes = ['Written Work', 'Quiz', 'Seatwork', 'Exam'];
                $weights = [
                    'Written Work' => 0.20,
                    'Quiz' => 0.20,
                    'Seatwork' => 0.20,
                    'Exam' => 0.40,
                ];

                $allAssessments = Assessment::where('class_id', $class->id)
                    ->where('grading_period', $selectedPeriod)
                    ->get()
                    ->groupBy('enrollment_id');

                $allGrades = Grade::where('class_id', $class->id)
                    ->where('grading_period', $selectedPeriod)
                    ->get()
                    ->keyBy('enrollment_id');

                $computedGrades = $activeEnrollments->map(function ($enrollment) use ($class, $selectedPeriod, $assessmentTypes, $weights, $allAssessments, $allGrades) {
                    $assessments = $allAssessments->get($enrollment->id, collect());

                    $categoryScores = [];
                    foreach ($assessmentTypes as $type) {
                        $typeAssessments = $assessments->where('type', $type);
                        $totalRaw = $typeAssessments->sum('raw_score');
                        $totalMax = $typeAssessments->sum('max_score');
                        $categoryScores[$type] = [
                            'raw' => $totalRaw,
                            'max' => $totalMax,
                            'percentage' => $totalMax > 0 ? round(($totalRaw / $totalMax) * 100, 2) : 0,
                        ];
                    }

                    $weightedSum = 0;
                    foreach ($categoryScores as $type => $scores) {
                        $weightedSum += $scores['percentage'] * ($weights[$type] ?? 0.25);
                    }

                    $existingGrade = $allGrades->get($enrollment->id);

                    return [
                        'enrollment_id' => $enrollment->id,
                        'student' => $enrollment->student,
                        'categories' => $categoryScores,
                        'computed_grade' => round($weightedSum, 2),
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
                    'classes', 'class', 'computedGrades', 'gradingPeriods', 'selectedPeriod', 'selectedClassId'
                ))->render(),
            ]);
        }

        return view('portal.teacher.computed-grades', compact(
            'classes', 'class', 'computedGrades', 'gradingPeriods', 'selectedPeriod', 'selectedClassId'
        ));
    }

    public function batchSubmitGrades(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'grading_period' => 'required|string|in:1st Term,2nd Term,3rd Term',
            'grades' => 'required|array',
            'grades.*.enrollment_id' => 'required|exists:enrollments,id',
            'grades.*.final_grade' => 'required|numeric|min:0|max:100',
        ]);

        $class = Classes::findOrFail($data['class_id']);
        if ($class->teacher_id !== auth()->id()) {
            abort(403);
        }

        foreach ($data['grades'] as $gradeData) {
            Grade::updateOrCreate(
                [
                    'enrollment_id' => $gradeData['enrollment_id'],
                    'class_id' => $class->id,
                    'grading_period' => $data['grading_period'],
                ],
                [
                    'final_grade' => $gradeData['final_grade'],
                    'status' => 'Pending',
                ]
            );
        }

        return back()->with('success', count($data['grades']) . ' grade(s) saved for ' . $data['grading_period'] . '.');
    }
}
