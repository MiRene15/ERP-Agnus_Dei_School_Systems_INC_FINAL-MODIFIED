<?php

use App\Models\Setting;

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
        return Setting::getValue('active_school_year', date('Y') . '-' . (date('Y') + 1));
    }
}
