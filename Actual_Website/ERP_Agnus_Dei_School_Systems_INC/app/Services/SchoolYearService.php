<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Spec: enrollment-payment-gating-calendar-holds.md §4.1 + §6.
 *
 * Single active school year running July → March. Registrar/Directress
 * announcing the new school year is the event that raises holds
 * (see HoldService gating in Slice 3).
 */
class SchoolYearService
{
    public const START_MONTH = 7;

    public const END_MONTH = 3;

    /**
     * "2027-2028" must be two consecutive years.
     */
    public static function isValidFormat(string $schoolYear): bool
    {
        if (! preg_match('/^(\d{4})-(\d{4})$/', $schoolYear, $m)) {
            return false;
        }

        return ((int) $m[2]) === ((int) $m[1]) + 1;
    }

    /**
     * July 1 of year 1 → March 31 of year 2.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function monthRange(string $schoolYear): array
    {
        [$startYear, $endYear] = array_map('intval', explode('-', $schoolYear));

        return [
            Carbon::create($startYear, self::START_MONTH, 1)->startOfMonth(),
            Carbon::create($endYear, self::END_MONTH, 1)->endOfMonth(),
        ];
    }

    public static function periodLabel(string $schoolYear): string
    {
        return $schoolYear . ' (Jul–Mar)';
    }

    public static function announcedYear(): ?string
    {
        $raw = Setting::getValue('announced_school_year', '');

        return $raw !== null && $raw !== '' ? $raw : null;
    }

    /**
     * Holds are visible iff a new school year was announced AND it is
     * the currently active year. Before announcement: no holds.
     */
    public static function isNewYearAnnounced(?string $activeYear = null): bool
    {
        $active = $activeYear ?? active_school_year();
        $announced = self::announcedYear();

        return $announced !== null && $announced === $active;
    }

    /**
     * Create + activate a single year. Only one active year may exist —
     * setting a new active year replaces the old one (never two actives).
     */
    public static function createAndActivate(string $schoolYear): void
    {
        DB::transaction(function () use ($schoolYear): void {
            \App\Models\FeeSchedule::firstOrCreate(
                ['grade_level' => 'Grade 1', 'term' => '1st Term', 'school_year' => $schoolYear],
                ['tuition_fee' => 0, 'misc_fee' => 0]
            );

            Setting::setValue('active_school_year', $schoolYear);
            Cache::forget('active_school_year');
            Cache::forget('all_school_years');
        });
    }

    /**
     * "Announce New School Year" — the hold-raising event. The announced
     * year becomes active and prior years stay read-only via existing
     * school_year_locked() semantics (caller locks the old year).
     */
    public static function announce(string $schoolYear): void
    {
        DB::transaction(function () use ($schoolYear): void {
            Setting::setValue('active_school_year', $schoolYear);
            Setting::setValue('announced_school_year', $schoolYear);
            Cache::forget('active_school_year');
            Cache::forget('setting_announced_school_year');
            Cache::forget('all_school_years');
        });
    }
}
