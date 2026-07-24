<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Setting;
use Illuminate\Http\Request;

class ReportCardController extends Controller
{
    public function index()
    {
        $query = Enrollment::with('student', 'section')
            ->where('status', 'Active')
            ->where('school_year', active_school_year());

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })->orWhereHas('section', function ($sq) use ($search) {
                    $sq->where('section_name', 'like', "%{$search}%");
                });
            });
        }

        if (request('grade_level') && request('grade_level') !== 'All') {
            $query->whereHas('section', function ($q) {
                $q->where('grade_level', request('grade_level'));
            });
        }

        $enrollments = $query->get()
            ->sortBy(fn($e) => $e->student?->last_name . ', ' . $e->student?->first_name)
            ->groupBy(fn($e) => $e->section?->grade_level ?? 'Unknown');

        return view('portal.registrar.report-cards.index', compact('enrollments'));
    }

    public function show(Enrollment $enrollment)
    {
        $enrollment->load('student', 'section.adviser', 'subjects.subject');

        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];

        $grades = Grade::where('enrollment_id', $enrollment->id)
            ->whereIn('grading_period', $gradingPeriods)
            ->with('schoolClass.subject')
            ->get()
            ->groupBy('class_id');

        $subjects = $enrollment->subjects->map(function ($class) use ($grades, $gradingPeriods) {
            $classGrades = $grades->get($class->id, collect());
            $row = ['subject' => $class->subject->name ?? 'N/A'];
            $total = 0;
            $count = 0;
            foreach ($gradingPeriods as $period) {
                $g = $classGrades->firstWhere('grading_period', $period);
                $row[$period] = $g ? number_format($g->final_grade, 2) : '—';
                if ($g) { $total += $g->final_grade; $count++; }
            }
            $avg = $count > 0 ? round($total / $count, 2) : 0;
            $row['final'] = $avg > 0 ? number_format($avg, 2) : '—';
            $row['remarks'] = $avg >= 75 ? 'Passed' : ($avg > 0 ? 'Failed' : '—');
            return (object) $row;
        });

        $overallAverage = $subjects->filter(fn($s) => is_numeric(str_replace(',', '', $s->final)))->avg(fn($s) => (float) str_replace(',', '', $s->final));

        return view('portal.registrar.report-cards.show', compact('enrollment', 'subjects', 'gradingPeriods', 'overallAverage'));
    }

    public function print(Enrollment $enrollment)
    {
        $enrollment->load('student', 'section.adviser', 'subjects.subject');

        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];

        $grades = Grade::where('enrollment_id', $enrollment->id)
            ->whereIn('grading_period', $gradingPeriods)
            ->with('schoolClass.subject')
            ->get()
            ->groupBy('class_id');

        $subjects = $enrollment->subjects->map(function ($class) use ($grades, $gradingPeriods) {
            $classGrades = $grades->get($class->id, collect());
            $row = ['subject' => $class->subject->name ?? 'N/A'];
            $total = 0;
            $count = 0;
            foreach ($gradingPeriods as $period) {
                $g = $classGrades->firstWhere('grading_period', $period);
                $row[$period] = $g ? number_format($g->final_grade, 2) : '—';
                if ($g) { $total += $g->final_grade; $count++; }
            }
            $avg = $count > 0 ? round($total / $count, 2) : 0;
            $row['final'] = $avg > 0 ? number_format($avg, 2) : '—';
            $row['remarks'] = $avg >= 75 ? 'Passed' : ($avg > 0 ? 'Failed' : '—');
            return (object) $row;
        });

        $overallAverage = $subjects->filter(fn($s) => is_numeric(str_replace(',', '', $s->final)))->avg(fn($s) => (float) str_replace(',', '', $s->final));

        $directressName = Setting::getValue('directress_name', '');
        $principalName = Setting::getValue('principal_name', '');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.registrar.report-cards.print', compact('enrollment', 'subjects', 'gradingPeriods', 'overallAverage', 'directressName', 'principalName'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream("report-card-{$enrollment->student->student_number}.pdf");
    }

    public function studentShow()
    {
        $student = auth()->user()->student;

        $enrollment = $student->enrollments()
            ->with('section.adviser', 'subjects.subject')
            ->where('status', 'Active')
            ->where('school_year', active_school_year())
            ->latest()
            ->first();

        if (!$enrollment) {
            return redirect()->route('student.dashboard')->with('error', 'No active enrollment found.');
        }

        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];

        $grades = Grade::where('enrollment_id', $enrollment->id)
            ->whereIn('grading_period', $gradingPeriods)
            ->with('schoolClass.subject')
            ->get()
            ->groupBy('class_id');

        $subjects = $enrollment->subjects->map(function ($class) use ($grades, $gradingPeriods) {
            $classGrades = $grades->get($class->id, collect());
            $row = ['subject' => $class->subject->name ?? 'N/A'];
            $total = 0;
            $count = 0;
            foreach ($gradingPeriods as $period) {
                $g = $classGrades->firstWhere('grading_period', $period);
                $row[$period] = $g ? number_format($g->final_grade, 2) : '—';
                if ($g) { $total += $g->final_grade; $count++; }
            }
            $avg = $count > 0 ? round($total / $count, 2) : 0;
            $row['final'] = $avg > 0 ? number_format($avg, 2) : '—';
            $row['remarks'] = $avg >= 75 ? 'Passed' : ($avg > 0 ? 'Failed' : '—');
            return (object) $row;
        });

        $overallAverage = $subjects->filter(fn($s) => is_numeric(str_replace(',', '', $s->final)))->avg(fn($s) => (float) str_replace(',', '', $s->final));

        return view('portal.student.report-card', compact('enrollment', 'subjects', 'gradingPeriods', 'overallAverage'));
    }
}
