<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            SystemRolesAndStaffSeeder::class,
            SubjectsAndSectionsSeeder::class,
            TeachersClassesSchedulesSeeder::class,
            FeeSchedulesSeeder::class,
            StudentsAndFeesSeeder::class,
            GradesAssessmentsSeeder::class,
            LibraryAndClinicSeeder::class,
            AnnouncementsTableSeeder::class,
            FixArNumbersSeeder::class,
        ]);
    }
}
