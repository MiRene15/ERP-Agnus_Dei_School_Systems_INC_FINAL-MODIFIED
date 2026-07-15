<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\User;
use App\Mail\GradesSubmittedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index()
    {
        $teacherId = auth()->id();
        $classes = Classes::with('subject', 'schedules')
            ->where('teacher_id', $teacherId)
            ->where('school_year', active_school_year())
            ->where('status', 'active')
            ->get();

        $today = now()->format('l');
        $todaySchedule = $classes->flatMap->schedules->filter(function ($s) use ($today) {
            return $s->day_of_week === $today;
        })->sortBy('start_time');

        $totalStudents = $classes->sum(function ($c) {
            return $c->enrollments()->where('status', 'Active')->count();
        });

        return view('portal.teacher.dashboard', compact('classes', 'todaySchedule', 'totalStudents'));
    }

    public function classes()
    {
        $teacherId = auth()->id();
        $classes = Classes::with('subject', 'schedules', 'teacher')
            ->where('teacher_id', $teacherId)
            ->where('school_year', active_school_year())
            ->where('status', 'active')
            ->get();

        return view('portal.teacher.classes', compact('classes'));
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

        $gradingPeriods = ['1st Semester', '2nd Semester', '3rd Semester'];
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
            'grading_period' => 'required|string|in:1st Semester,2nd Semester,3rd Semester',
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
            'grading_period' => 'required|string|in:1st Semester,2nd Semester,3rd Semester',
        ]);

        $gradeCount = Grade::where('class_id', $class->id)
            ->where('grading_period', $data['grading_period'])
            ->where('status', 'Pending')
            ->update(['status' => 'Submitted']);

        $recipients = User::whereIn('role_id', [1, 2])->pluck('email')->filter();
        foreach ($recipients as $email) {
            Mail::to($email)->queue(new GradesSubmittedMail($class, $data['grading_period']));
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

        $gradingPeriods = ['1st Semester', '2nd Semester', '3rd Semester'];
        $selectedPeriod = request('grading_period', $gradingPeriods[0]);

        $assessmentTypes = ['Written Work', 'Performance Task', 'Semestral Assessment'];

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
            'grading_period' => 'required|string|in:1st Semester,2nd Semester,3rd Semester',
            'assessments' => 'required|array',
            'assessments.*' => 'required|array',
            'assessments.*.*.type' => 'required|string|in:Written Work,Performance Task,Semestral Assessment',
            'assessments.*.*.title' => 'required|string|max:255',
            'assessments.*.*.raw_score' => 'required|numeric|min:0',
            'assessments.*.*.max_score' => 'required|numeric|min:0',
        ]);

        Assessment::where('class_id', $class->id)
            ->where('grading_period', $data['grading_period'])
            ->delete();

        $inserts = [];
        foreach ($data['assessments'] as $enrollmentId => $items) {
            foreach ($items as $item) {
                $inserts[] = [
                    'enrollment_id' => $enrollmentId,
                    'class_id' => $class->id,
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'raw_score' => $item['raw_score'],
                    'max_score' => $item['max_score'],
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

    public function schedule()
    {
        $teacherId = auth()->id();
        $classes = Classes::with('subject', 'schedules')
            ->where('teacher_id', $teacherId)
            ->where('school_year', active_school_year())
            ->where('status', 'active')
            ->get();

        $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        $schedulesByDay = [];
        foreach ($weekDays as $day) {
            $schedulesByDay[$day] = Schedule::whereHas('schoolClass', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)
                    ->where('school_year', active_school_year())
                    ->where('status', 'active');
            })
            ->where('day_of_week', $day)
            ->with('schoolClass.subject')
            ->orderBy('start_time')
            ->get();
        }

        return view('portal.teacher.schedule', compact('classes', 'weekDays', 'schedulesByDay'));
    }
}
