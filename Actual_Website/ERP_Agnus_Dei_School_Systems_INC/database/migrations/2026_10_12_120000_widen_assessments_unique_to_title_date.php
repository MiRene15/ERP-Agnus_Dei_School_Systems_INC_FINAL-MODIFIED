<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE assessments DROP CONSTRAINT IF EXISTS assessments_enrollment_class_type_period_unique');
        $hasConstraint = DB::select("SELECT conname FROM pg_constraint WHERE conname = 'assessments_enrollment_class_type_period_title_date_unique'");
        if (empty($hasConstraint)) {
            DB::statement('ALTER TABLE assessments ADD CONSTRAINT assessments_enrollment_class_type_period_title_date_unique UNIQUE (enrollment_id, class_id, type, grading_period, title, assessment_date)');
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE assessments DROP CONSTRAINT IF EXISTS assessments_enrollment_class_type_period_title_date_unique');
        $hasConstraint = DB::select("SELECT conname FROM pg_constraint WHERE conname = 'assessments_enrollment_class_type_period_unique'");
        if (empty($hasConstraint)) {
            DB::statement('ALTER TABLE assessments ADD CONSTRAINT assessments_enrollment_class_type_period_unique UNIQUE (enrollment_id, class_id, type, grading_period)');
        }
    }
};
