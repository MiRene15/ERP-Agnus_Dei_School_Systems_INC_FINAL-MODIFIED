<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Interim MATATAG transmutation (spec: teacher-encode-grades-deped-layout.md §6).
 *
 * INTERIM — uses a logical linear map until the registrar supplies the
 * official DO 15, s. 2026 adjusted band table. Anchors (confirmed):
 * 100 → 100, 70.00 → 75 (passing), 0 → 60 (minimum reportable).
 * Below 70 maps into 60–74. Swap this one file when the official bands land.
 */
class TransmutationService
{
    public const INTERIM = true;

    public function transmute(float $initialGrade): int
    {
        $initial = max(0.0, min(100.0, $initialGrade));

        if ($initial >= 70.0) {
            $result = 75.0 + ($initial - 70.0) * (25.0 / 30.0);
        } else {
            $result = 60.0 + ($initial / 70.0) * 15.0;
        }

        return (int) round(min(100.0, max(60.0, $result)));
    }
}
