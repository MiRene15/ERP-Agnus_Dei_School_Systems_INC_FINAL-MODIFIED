<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Spec: enrollment-payment-gating-calendar-holds.md §4.2 + §6.
 *
 * Grading closes exactly 1 week after each examination week, per period.
 * Exam-end dates are stored as settings (YYYY-MM-DD); rescheduling moves
 * the close date automatically (close = exam-end + 7 days).
 */
class GradingCloseService
{
    public const PERIODS = ['1st Term', '2nd Term', '3rd Term'];

    public static function settingKey(string $period): string
    {
        return 'exam_end_' . str_replace(' ', '', $period);
    }

    public static function getExamEnd(string $period): ?Carbon
    {
        $raw = Setting::getValue(self::settingKey($period), '');

        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function setExamEnd(string $period, string $dateYmd): void
    {
        Setting::setValue(self::settingKey($period), $dateYmd);
        Cache::forget('setting_' . self::settingKey($period));
    }

    /**
     * Close = 7 days after the last exam day. Null = no close scheduled.
     */
    public static function getCloseDate(string $period): ?Carbon
    {
        $examEnd = self::getExamEnd($period);

        return $examEnd !== null ? $examEnd->copy()->addDays(7) : null;
    }

    public static function isClosed(string $period, ?Carbon $now = null): bool
    {
        $close = self::getCloseDate($period);

        if ($close === null) {
            return false;
        }

        return ($now ?? Carbon::now())->startOfDay()->greaterThan($close);
    }

    public static function banner(string $period): ?string
    {
        $close = self::getCloseDate($period);

        if ($close === null) {
            return null;
        }

        return self::isClosed($period)
            ? 'Closed — request an edit'
            : 'Closes ' . $close->format('M d, Y');
    }
}
