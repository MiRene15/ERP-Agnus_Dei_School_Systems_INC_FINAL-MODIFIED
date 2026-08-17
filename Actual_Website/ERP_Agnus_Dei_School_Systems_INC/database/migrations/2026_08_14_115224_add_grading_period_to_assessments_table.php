<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicates — keep lowest id per unique group
        DB::statement('
            DELETE FROM assessments
            WHERE id NOT IN (
                SELECT MIN(id)
                FROM assessments
                GROUP BY enrollment_id, class_id, type, grading_period
            )
        ');

        $hasConstraint = DB::select("SELECT conname FROM pg_constraint WHERE conname = 'assessments_enrollment_class_type_period_unique'");
        if (empty($hasConstraint)) {
            DB::statement('ALTER TABLE assessments ADD CONSTRAINT assessments_enrollment_class_type_period_unique UNIQUE (enrollment_id, class_id, type, grading_period)');
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE assessments DROP CONSTRAINT IF EXISTS assessments_enrollment_class_type_period_unique');
    }
};
