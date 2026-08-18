<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // enrollments - most frequently queried table
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index('status');
            $table->index('school_year');
            $table->index(['student_id', 'status']);
            $table->index(['student_id', 'school_year', 'status']);
        });

        // classes
        Schema::table('classes', function (Blueprint $table) {
            $table->index('teacher_id');
            $table->index('grade_level');
            $table->index(['grade_level', 'school_year', 'status']);
        });

        // payments
        Schema::table('payments', function (Blueprint $table) {
            $table->index('cashier_id');
            $table->index('payment_date');
            $table->index(['payment_date', 'ledger_id']);
        });

        // assessments
        Schema::table('assessments', function (Blueprint $table) {
            $table->index(['class_id', 'grading_period']);
            $table->index(['enrollment_id', 'class_id', 'grading_period']);
        });

        // student_ledgers
        Schema::table('student_ledgers', function (Blueprint $table) {
            $table->index('it_confirmed_at');
        });

        // fee_schedules
        Schema::table('fee_schedules', function (Blueprint $table) {
            $table->index(['grade_level', 'school_year', 'term']);
        });

        // activity_log
        Schema::table('activity_log', function (Blueprint $table) {
            $table->index('causer_id');
            $table->index('event');
            $table->index('created_at');
        });

        // library_transactions
        Schema::table('library_transactions', function (Blueprint $table) {
            $table->index('status');
            $table->index(['status', 'return_date']);
            $table->index('book_id');
        });

        // students
        Schema::table('students', function (Blueprint $table) {
            $table->index('status');
            $table->index('user_id');
        });

        // admissions
        Schema::table('admissions', function (Blueprint $table) {
            $table->index('status');
            $table->index('school_year');
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['school_year']);
            $table->dropIndex(['student_id', 'status']);
            $table->dropIndex(['student_id', 'school_year', 'status']);
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropIndex(['teacher_id']);
            $table->dropIndex(['grade_level']);
            $table->dropIndex(['grade_level', 'school_year', 'status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['cashier_id']);
            $table->dropIndex(['payment_date']);
            $table->dropIndex(['payment_date', 'ledger_id']);
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex(['class_id', 'grading_period']);
            $table->dropIndex(['enrollment_id', 'class_id', 'grading_period']);
        });

        Schema::table('student_ledgers', function (Blueprint $table) {
            $table->dropIndex(['it_confirmed_at']);
        });

        Schema::table('fee_schedules', function (Blueprint $table) {
            $table->dropIndex(['grade_level', 'school_year', 'term']);
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['causer_id']);
            $table->dropIndex(['event']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('library_transactions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['status', 'return_date']);
            $table->dropIndex(['book_id']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('admissions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['school_year']);
            $table->dropIndex(['student_id', 'status']);
        });
    }
};
