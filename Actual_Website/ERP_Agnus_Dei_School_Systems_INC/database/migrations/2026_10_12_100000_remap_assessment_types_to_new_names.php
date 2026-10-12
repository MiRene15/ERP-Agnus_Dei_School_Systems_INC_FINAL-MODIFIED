<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remap every legacy assessment type to the current canonical list
     * (Assessment::ASSESSMENT_TYPES) BEFORE the check constraint in
     * 2026_10_12_103157 is (re)applied. Idempotent — zero matching rows
     * is a successful no-op. Spec: grade-assessment-save-post-500-fix.md.
     */
    public function up(): void
    {
        // Drop the check first: the current constraint (middle-era list)
        // rejects the new names, so remapping must happen unconstrained.
        DB::statement('ALTER TABLE assessments DROP CONSTRAINT IF EXISTS assessments_type_check');

        DB::table('assessments')->whereIn('type', ['Written Work', 'Quiz', 'Seatwork'])->update(['type' => 'Written Works']);
        DB::table('assessments')->where('type', 'Exam')->update(['type' => 'Quarterly Assessment']);
        DB::table('assessments')->where('type', 'Performance Task')->update(['type' => 'Performance Tasks']);
        DB::table('assessments')->where('type', 'Semestral Assessment')->update(['type' => 'Quarterly Assessment']);

        $hasConstraint = DB::select("SELECT conname FROM pg_constraint WHERE conname = 'assessments_type_check'");
        if (empty($hasConstraint)) {
            DB::statement("ALTER TABLE assessments ADD CONSTRAINT assessments_type_check CHECK (type IN ('Written Works', 'Performance Tasks', 'Quarterly Assessment'))");
        }
    }

    public function down(): void
    {
        // Data remap is intentionally not reversed.
    }
};
