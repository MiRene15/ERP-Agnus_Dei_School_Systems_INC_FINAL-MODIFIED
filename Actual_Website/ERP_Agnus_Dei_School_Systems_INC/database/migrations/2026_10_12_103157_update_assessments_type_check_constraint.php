<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE assessments DROP CONSTRAINT IF EXISTS assessments_type_check');
        DB::statement("ALTER TABLE assessments ADD CONSTRAINT assessments_type_check CHECK (type IN ('Written Works', 'Performance Tasks', 'Quarterly Assessment'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE assessments DROP CONSTRAINT IF EXISTS assessments_type_check');
        DB::statement("ALTER TABLE assessments ADD CONSTRAINT assessments_type_check CHECK (type IN ('Written Work', 'Quiz', 'Seatwork', 'Exam'))");
    }
};
