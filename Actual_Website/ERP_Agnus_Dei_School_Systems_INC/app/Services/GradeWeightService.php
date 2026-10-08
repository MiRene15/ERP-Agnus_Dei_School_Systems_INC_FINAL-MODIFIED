<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subject;

class GradeWeightService
{
    /**
     * Final school weights (spec: computed-single-grade.md §9). Every row
     * totals 100. Keys are Written Works / Performance Tasks /
     * Quarterly Assessment — complete words everywhere, no abbreviations.
     *
     * @var array<string, array{written_works: int, performance_tasks: int, quarterly_assessment: int}>
     */
    public const WEIGHTS = [
        'lang' => ['written_works' => 30, 'performance_tasks' => 50, 'quarterly_assessment' => 20],
        'scimath' => ['written_works' => 40, 'performance_tasks' => 40, 'quarterly_assessment' => 20],
        'mapeh' => ['written_works' => 40, 'performance_tasks' => 40, 'quarterly_assessment' => 20],
        'core' => ['written_works' => 25, 'performance_tasks' => 50, 'quarterly_assessment' => 25],
        'shs_other' => ['written_works' => 25, 'performance_tasks' => 45, 'quarterly_assessment' => 30],
        'shs_applied' => ['written_works' => 35, 'performance_tasks' => 40, 'quarterly_assessment' => 25],
        'tvl_other' => ['written_works' => 20, 'performance_tasks' => 60, 'quarterly_assessment' => 20],
        'tvl_applied' => ['written_works' => 20, 'performance_tasks' => 60, 'quarterly_assessment' => 20],
    ];

    /**
     * Resolve a class's weights row from its subject. Unmapped subjects fall
     * back to their band's largest group row — and the applied group is
     * always returned for on-screen labelling, so a mis-map is visible,
     * never silent.
     *
     * @return array{group: string, label: string, written_works: int, performance_tasks: int, quarterly_assessment: int}
     */
    public function forClass(?Subject $subject, ?string $gradeLevel = null): array
    {
        $name = strtolower($subject->name ?? '');
        $category = strtolower($subject->category ?? '');
        $grade = strtolower($gradeLevel ?? $subject->grade_level ?? '');

        $isShs = str_contains($grade, 'grade 11') || str_contains($grade, 'grade 12') || str_contains($grade, 'shs');

        if ($isShs && $category === 'tvl') {
            $group = $this->isAppliedSubject($name) ? 'tvl_applied' : 'tvl_other';
            $band = 'TVL';
        } elseif ($isShs) {
            if ($category === 'core') {
                $group = 'core';
            } elseif ($this->isAppliedSubject($name)) {
                $group = 'shs_applied';
            } else {
                $group = 'shs_other';
            }
            $band = 'SHS';
        } else {
            $group = $this->basicEdGroup($name);
            $band = 'Grades 1-10';
        }

        $weights = self::WEIGHTS[$group];

        return [
            'group' => $group,
            'label' => $band . ' · ' . $this->groupLabel($group) . ' — Written Works ' . $weights['written_works'] . '%, Performance Tasks ' . $weights['performance_tasks'] . '%, Quarterly Assessment ' . $weights['quarterly_assessment'] . '%',
            'written_works' => $weights['written_works'],
            'performance_tasks' => $weights['performance_tasks'],
            'quarterly_assessment' => $weights['quarterly_assessment'],
        ];
    }

    private function isAppliedSubject(string $name): bool
    {
        foreach (['work immersion', 'research', 'business enterprise', 'exhibit', 'performance'] as $keyword) {
            if ($keyword !== '' && str_contains($name, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function basicEdGroup(string $name): string
    {
        foreach (['science', 'math'] as $keyword) {
            if (str_contains($name, $keyword)) {
                return 'scimath';
            }
        }

        foreach (['mapeh', 'music', 'arts', 'physical education', 'pe ', 'health', 'epp', 'tle', 'technology', 'livelihood'] as $keyword) {
            if (str_contains($name, $keyword)) {
                return 'mapeh';
            }
        }

        return 'lang';
    }

    private function groupLabel(string $group): string
    {
        return match ($group) {
            'lang' => 'Languages / AP / ESP',
            'scimath' => 'Science / Math',
            'mapeh' => 'MAPEH / EPP / TLE',
            'core' => 'Core subjects',
            'shs_other' => 'All other subjects',
            'shs_applied' => 'Work Immersion / Research / Business Enterprise Simulation / Exhibit Performance',
            'tvl_other' => 'All other subjects',
            'tvl_applied' => 'Work Immersion / Research / Exhibit Performance',
            default => $group,
        };
    }
}
