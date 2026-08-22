<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Payment;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function enrollments()
    {
        $enrollments = Enrollment::with('student', 'section.adviser')
            ->where('status', 'Active')
            ->where('school_year', active_school_year())
            ->get();

        $filename = 'enrollments-' . active_school_year() . '-' . $enrollments->count() . 'rows.csv';
        return $this->streamCsv($filename, function ($fh) use ($enrollments) {
            fputcsv($fh, ['LRN', 'First Name', 'Last Name', 'Grade Level', 'Section', 'Adviser', 'Status']);
            foreach ($enrollments as $e) {
                fputcsv($fh, [
                    $e->student?->student_number,
                    $e->student?->first_name,
                    $e->student?->last_name,
                    $e->section?->grade_level,
                    $e->section?->section_name,
                    $e->section?->adviser?->name,
                    $e->status,
                ]);
            }
        });
    }

    public function grades()
    {
        $enrollments = Enrollment::with('student', 'section', 'subjects.subject')
            ->where('status', 'Active')
            ->where('school_year', active_school_year())
            ->get();

        $periods = ['1st Term', '2nd Term', '3rd Term'];

        $allGrades = Grade::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->whereIn('grading_period', $periods)
            ->get()
            ->groupBy('enrollment_id');

        $passing = (int) \App\Models\Setting::getValue('passing_grade', '75');
        $filename = 'grades-' . active_school_year() . '-' . $enrollments->count() . 'students.csv';
        return $this->streamCsv($filename, function ($fh) use ($enrollments, $periods, $allGrades, $passing) {
            fputcsv($fh, array_merge(['LRN', 'Name', 'Grade', 'Section', 'Subject'], $periods, ['Final', 'Remarks']));
            foreach ($enrollments as $e) {
                $grades = $allGrades->get($e->id, collect())->groupBy('class_id');
                foreach ($e->subjects as $class) {
                    $classGrades = $grades->get($class->id, collect());
                    $total = 0; $count = 0;
                    $row = [$e->student?->student_number, ($e->student?->first_name ?? '') . ' ' . ($e->student?->last_name ?? ''), $e->section?->grade_level, $e->section?->section_name, $class->subject?->name];
                    foreach ($periods as $p) {
                        $g = $classGrades->firstWhere('grading_period', $p);
                        $row[] = $g ? $g->final_grade : '';
                        if ($g) { $total += $g->final_grade; $count++; }
                    }
                    $avg = $count > 0 ? round($total / $count, 2) : '';
                    $row[] = $avg ?: '';
                    $row[] = $avg >= $passing ? 'Passed' : ($avg > 0 ? 'Failed' : '');
                    fputcsv($fh, $row);
                }
            }
        });
    }

    public function collections()
    {
        $payments = Payment::with('ledger.student')
            ->whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->orderBy('payment_date')
            ->get();

        $filename = 'collections-' . now()->format('Y-m') . '-' . $payments->count() . 'rows.csv';
        return $this->streamCsv($filename, function ($fh) use ($payments) {
            fputcsv($fh, ['Receipt #', 'Student', 'LRN', 'Amount', 'Payment Date', 'Cashier']);
            foreach ($payments as $p) {
                fputcsv($fh, [
                    $p->receipt_number,
                    $p->ledger?->student?->first_name . ' ' . $p->ledger?->student?->last_name,
                    $p->ledger?->student?->student_number,
                    $p->amount_paid,
                    $p->payment_date,
                    $p->cashier?->name,
                ]);
            }
        });
    }

    private function streamCsv($filename, $callback)
    {
        return response()->stream(function () use ($callback) {
            $fh = fopen('php://output', 'w');
            $callback($fh);
            fclose($fh);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
