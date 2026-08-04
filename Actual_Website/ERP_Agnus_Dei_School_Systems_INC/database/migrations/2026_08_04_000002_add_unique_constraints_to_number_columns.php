<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unique constraints on student_number and application_number already exist
        // in the original migration (2026_03_29_142152_create_erp_core_tables.php).
        // This migration is a no-op — the race-condition fix is in the model code
        // (lockForUpdate() in Student::generateStudentNumber and Admission::boot).
    }

    public function down(): void
    {
        //
    }
};
