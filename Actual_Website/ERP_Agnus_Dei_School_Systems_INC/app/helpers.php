<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('log_activity')) {
    function log_activity($subject, string $event, string $description = null, array $properties = []) {
        \App\Models\ActivityLog::create([
            'subject_type' => is_object($subject) ? get_class($subject) : $subject,
            'subject_id' => is_object($subject) ? $subject->id : null,
            'causer_id' => auth()->id(),
            'event' => $event,
            'description' => $description,
            'properties' => $properties,
        ]);
    }
}

if (!function_exists('active_school_year')) {
    function active_school_year(): string
    {
        return Cache::remember('active_school_year', 3600, function () {
            return Setting::getValue('active_school_year', date('Y') . '-' . (date('Y') + 1));
        });
    }
}

if (!function_exists('all_school_years')) {    function all_school_years(): \Illuminate\Support\Collection
    {
        return Cache::remember('all_school_years', 3600, function () {
            $fromEnrollments = \App\Models\Enrollment::distinct()->pluck('school_year');
            $fromAdmissions = \App\Models\Admission::distinct()->pluck('school_year');
            $fromFees = \App\Models\FeeSchedule::distinct()->pluck('school_year');

            return $fromEnrollments->merge($fromAdmissions)->merge($fromFees)
                ->filter()
                ->unique()
                ->sortDesc()
                ->values();
        });
    }
}

if (!function_exists('locked_school_years')) {
    function locked_school_years(): array
    {
        $raw = Cache::remember('setting_locked_school_years', 3600, function () {
            return Setting::getValue('locked_school_years', '');
        });
        return $raw ? array_map('trim', explode(',', $raw)) : [];
    }
}

if (!function_exists('school_year_locked')) {
    // A locked year is frozen: no new enrollments, payments, grades,
    // schedules, fees, or promotions may touch it.
    function school_year_locked(?string $schoolYear): bool
    {
        if (!$schoolYear) return false;
        return in_array($schoolYear, locked_school_years(), true);
    }
}
