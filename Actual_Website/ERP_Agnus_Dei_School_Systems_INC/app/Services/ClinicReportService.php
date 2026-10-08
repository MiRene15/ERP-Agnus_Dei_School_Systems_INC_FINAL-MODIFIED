<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClinicLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClinicReportService
{
    /**
     * Shared clinic read for the directress tab and the nurse single.
     * Anchored on visit_date so both screens total the same set.
     *
     * @return array{
     *     logs: Collection,
     *     totalVisits: int,
     *     uniquePatients: int,
     *     referralsOut: int,
     *     activeDays: int,
     *     byGrade: Collection,
     *     topSymptoms: Collection,
     *     openCases: int,
     *     dateFrom: string,
     *     dateTo: string
     * }
     */
    public function rangeData(string $dateFrom, string $dateTo): array
    {
        $logs = ClinicLog::with('student.enrollments.section')
            ->whereBetween('visit_date', [$dateFrom, $dateTo . ' 23:59:59'])
            ->orderByDesc('visit_date')
            ->get();

        $totalVisits = $logs->count();
        $uniquePatients = $logs->pluck('student_id')->unique()->count();
        $referralsOut = $logs->whereNotNull('referred_to')->count();
        $activeDays = $logs->groupBy(fn($l) => Carbon::parse($l->visit_date)->format('Y-m-d'))->count();

        $gradeRank = ['Kinder'=>0,'Grade 1'=>1,'Grade 2'=>2,'Grade 3'=>3,'Grade 4'=>4,'Grade 5'=>5,'Grade 6'=>6,'Grade 7'=>7,'Grade 8'=>8,'Grade 9'=>9,'Grade 10'=>10,'Grade 11'=>11,'Grade 12'=>12];
        $byGrade = $logs->groupBy(fn($l) => $l->student?->enrollments->where('status', 'Active')->first()?->section?->grade_level ?? 'Unknown')
            ->map->count()
            ->sortBy(fn($_, $k) => $gradeRank[$k] ?? 99);

        $topSymptoms = $logs->pluck('symptoms')->filter()->flatMap(fn($s) => array_map('trim', explode(',', $s)))
            ->countBy()->sortDesc()->take(8);
        $openCases = $logs->where('is_open', true)->count();

        return [
            'logs' => $logs,
            'totalVisits' => $totalVisits,
            'uniquePatients' => $uniquePatients,
            'referralsOut' => $referralsOut,
            'activeDays' => $activeDays,
            'byGrade' => $byGrade,
            'topSymptoms' => $topSymptoms,
            'openCases' => $openCases,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }
}
