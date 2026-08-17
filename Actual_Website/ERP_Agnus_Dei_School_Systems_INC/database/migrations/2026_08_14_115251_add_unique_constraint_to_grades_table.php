<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hasConstraint = DB::select("SELECT conname FROM pg_constraint WHERE conname = 'grades_enrollment_class_period_unique'");
        if (empty($hasConstraint)) {
            DB::statement('ALTER TABLE grades ADD CONSTRAINT grades_enrollment_class_period_unique UNIQUE (enrollment_id, class_id, grading_period)');
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE grades DROP CONSTRAINT IF EXISTS grades_enrollment_class_period_unique');
    }
};
