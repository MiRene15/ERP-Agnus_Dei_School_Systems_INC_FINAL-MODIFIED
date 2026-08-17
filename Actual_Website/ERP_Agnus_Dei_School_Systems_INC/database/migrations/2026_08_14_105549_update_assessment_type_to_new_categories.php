<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the check constraint first
        DB::statement('ALTER TABLE assessments DROP CONSTRAINT IF EXISTS assessments_type_check');

        // Map old types to new types
        DB::table('assessments')->where('type', 'Performance Task')->update(['type' => 'Seatwork']);
        DB::table('assessments')->where('type', 'Semestral Assessment')->update(['type' => 'Exam']);

        // Change column to varchar(30) to support new types
        DB::statement('ALTER TABLE assessments ALTER COLUMN type TYPE VARCHAR(30)');

        // Add new check constraint
        DB::statement("ALTER TABLE assessments ADD CONSTRAINT assessments_type_check CHECK (type IN ('Written Work', 'Quiz', 'Seatwork', 'Exam'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE assessments DROP CONSTRAINT IF EXISTS assessments_type_check');

        DB::table('assessments')->where('type', 'Seatwork')->update(['type' => 'Performance Task']);
        DB::table('assessments')->where('type', 'Exam')->update(['type' => 'Semestral Assessment']);

        DB::statement('ALTER TABLE assessments ALTER COLUMN type TYPE VARCHAR(30)');

        DB::statement("ALTER TABLE assessments ADD CONSTRAINT assessments_type_check CHECK (type IN ('Written Work', 'Performance Task', 'Semestral Assessment'))");
    }
};
