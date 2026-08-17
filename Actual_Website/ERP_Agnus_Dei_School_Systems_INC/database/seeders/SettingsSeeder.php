<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'school_name', 'value' => 'Agnus Dei School Systems Inc.'],
            ['key' => 'school_address', 'value' => 'Brgy. Catmon, Pandan, Antique'],
            ['key' => 'school_year', 'value' => '2026-2027'],
            ['key' => 'current_term', 'value' => '1st Term'],
            ['key' => 'contact_email', 'value' => 'info@agnusdei.edu.ph'],
            ['key' => 'contact_phone', 'value' => '(036) 279-0000'],
        ];

        foreach ($settings as $s) {
            DB::table('settings')->updateOrInsert(
                ['key' => $s['key']],
                ['value' => $s['value'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
