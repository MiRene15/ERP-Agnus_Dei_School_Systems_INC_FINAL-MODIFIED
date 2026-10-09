<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subject;

class GradeWeightService
{
    /**
     * MATATAG weights (spec: teacher-encode-grades-deped-layout.md §6, DO 15 s. 2026).
     * Every row totals 100, except the two QA-None rows which total 100 across
     * WW + PT (quarterly_assessment = 0 means skip QA math, show `–`).
     * Keys are Written Works / Performance Tasks / Quarterly Assessment.
     *
     * @var array<string, array{written_works: int, performance_tasks: int, quarterly_assessment: int}>
     */
    public const WEIGHTS = [
        'g410_core' => ['written_works' => 20, 'performance_tasks' => 50, 'quarterly_assessment' => 30],
        'g410_tle' => ['written_works' => 20, 'performance_tasks' => 60, 'quarterly_assessment' => 20],
        'shs_core' => ['written_works' => 20, 'performance_tasks' => 50, 'quarterly_assessment' => 30],
        'shs_field' => ['written_works' => 15, 'performance_tasks' => 70, 'quarterly_assessment' => 15],
        'shs_arts' => ['written_works' => 20, 'performance_tasks' => 60, 'quarterly_assessment' => 20],
        'shs_research' => ['written_works' => 40, 'performance_tasks' => 60, 'quarterly_assessment' => 0],
        'shs_techpro' => ['written_works' => 15, 'performance_tasks' => 65, 'quarterly_assessment' => 20],
        'shs_immersion' => ['written_works' => 20, 'performance_tasks' => 80, 'quarterly_assessment' => 0],
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
            $group = $this->shsGroup($name, $category);
            $band = 'SHS';
        } elseif ($isShs) {
            $group = $this->shsGroup($name, $category);
            $band = 'SHS';
        } else {
            $group = $this->basicEdGroup($name);
            $band = 'Grades 4–10';
        }

        $weights = self::WEIGHTS[$group];

        $qaLabel = $weights['quarterly_assessment'] > 0
            ? 'Quarterly Assessment ' . $weights['quarterly_assessment'] . '%'
            : 'Quarterly Assessment — skipped';

        return [
            'group' => $group,
            'label' => $band . ' · ' . $this->groupLabel($group) . ' — Written Works ' . $weights['written_works'] . '%, Performance Tasks ' . $weights['performance_tasks'] . '%, ' . $qaLabel,
            'written_works' => $weights['written_works'],
            'performance_tasks' => $weights['performance_tasks'],
            'quarterly_assessment' => $weights['quarterly_assessment'],
        ];
    }

    private function shsGroup(string $name, string $category): string
    {
        if (str_contains($name, 'work immersion')) {
            return 'shs_immersion';
        }

        if (str_contains($name, 'research') || str_contains($name, 'design and innovation') || str_contains($name, 'design innovation')) {
            return 'shs_research';
        }

        if (str_contains($name, 'field exposure') || str_contains($name, 'arts apprenticeship') || str_contains($name, 'creative production')) {
            return 'shs_field';
        }

        if (str_contains($name, 'techpro') || str_contains($name, 'tech-pro') || str_contains($name, 'technical professional')) {
            return 'shs_techpro';
        }

        if (str_contains($name, 'arts') || str_contains($name, 'sports') || str_contains($name, 'health') || str_contains($name, 'wellness')) {
            return 'shs_arts';
        }

        return 'shs_core';
    }

    private function basicEdGroup(string $name): string
    {
        foreach (['mapeh', 'music', 'arts', 'physical education', 'pe ', 'health', 'epp', 'tle', 'technology', 'livelihood', 'pangkabuhayan'] as $keyword) {
            if ($keyword !== '' && str_contains($name, $keyword)) {
                return 'g410_tle';
            }
        }

        return 'g410_core';
    }

    private function groupLabel(string $group): string
    {
        return match ($group) {
            'g410_core' => 'English/Filipino/Math/Science/AP/GMRC-Values (20/50/30)',
            'g410_tle' => 'EPP / TLE / MAPEH (20/60/20)',
            'shs_core' => 'Core + Other Academic Electives',
            'shs_field' => 'Field Exposure / Arts Apprenticeship / Creative Production & Innovation',
            'shs_arts' => 'Arts, Sports, Health & Wellness Electives',
            'shs_research' => 'Research Electives / Design and Innovation (no QA)',
            'shs_techpro' => 'TechPro Electives',
            'shs_immersion' => 'Work Immersion (no QA)',
            default => $group,
        };
    }
}
