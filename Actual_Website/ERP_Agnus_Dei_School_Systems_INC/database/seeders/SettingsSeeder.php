<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'school_name', 'value' => 'Agnus Dei School Systems Inc.'],
            ['key' => 'school_address', 'value' => 'Brgy. Catmon, Pandan, Antique'],
            ['key' => 'active_school_year', 'value' => '2026-2027'],
            ['key' => 'school_year', 'value' => '2026-2027'],
            ['key' => 'current_term', 'value' => '1st Term'],
            ['key' => 'contact_email', 'value' => 'info@agnusdei.edu.ph'],
            ['key' => 'contact_phone', 'value' => '(036) 279-0000'],
            // Spec seed-data-foundations.md §4: history locked, next year ready.
            // Announced (2027-2028) differs from active (2026-2027) so no
            // holds fire (holds require announced == active).
            ['key' => 'locked_school_years', 'value' => '2024-2025,2025-2026'],
            ['key' => 'announced_school_year', 'value' => '2027-2028'],
        ];

        foreach ($settings as $s) {
            DB::table('settings')->updateOrInsert(
                ['key' => $s['key']],
                ['value' => $s['value'], 'created_at' => now(), 'updated_at' => now()]
            );
        }

        Cache::forget('active_school_year');
        Cache::forget('setting_locked_school_years');
        Cache::forget('setting_announced_school_year');
    }
}
