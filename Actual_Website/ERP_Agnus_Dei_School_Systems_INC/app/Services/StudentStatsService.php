<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Support\Collection;

class StudentStatsService
{
    /**
     * Shared Active-enrollment counts for the directress tab and the registrar single.
     * Same live source both screens read, so the numbers always agree.
     * Grouped passes only — no per-row query scaling at enrollment peak.
     *
     * @return array{
     *     byGrade: Collection,
     *     bySection: Collection,
     *     byYear: Collection,
     *     byGender: Collection,
     *     byStrand: Collection,
     *     total: int
     * }
     */
    public function studentStatsData(): array
    {
        $gradeRank = ['Kinder'=>0,'Grade 1'=>1,'Grade 2'=>2,'Grade 3'=>3,'Grade 4'=>4,'Grade 5'=>5,'Grade 6'=>6,'Grade 7'=>7,'Grade 8'=>8,'Grade 9'=>9,'Grade 10'=>10,'Grade 11'=>11,'Grade 12'=>12];
        $byGrade = Enrollment::with('section')->where('status', 'Active')->get()
            ->groupBy(fn($e) => $e->section?->grade_level ?? 'Unknown')->map->count()
            ->sortBy(fn($_, $k) => $gradeRank[$k] ?? 99);
        $bySection = Enrollment::with('section')->where('status', 'Active')->get()
            ->groupBy(fn($e) => $e->section?->section_name ?? 'Unknown')->map->count()->sortKeys();
        $byYear = Enrollment::where('status', 'Active')->get()->groupBy('school_year')->map->count()->sortKeysDesc();
        $byGender = Student::whereHas('enrollments', fn($q) => $q->where('status', 'Active'))->get()
            ->groupBy(fn($s) => $s->gender ?? 'Unknown')->map->count();
        $byStrand = Enrollment::where('status', 'Active')->whereNotNull('strand')->get()->groupBy('strand')->map->count();
        $total = (int) $byGrade->sum();

        return [
            'byGrade' => $byGrade,
            'bySection' => $bySection,
            'byYear' => $byYear,
            'byGender' => $byGender,
            'byStrand' => $byStrand,
            'total' => $total,
        ];
    }
}
