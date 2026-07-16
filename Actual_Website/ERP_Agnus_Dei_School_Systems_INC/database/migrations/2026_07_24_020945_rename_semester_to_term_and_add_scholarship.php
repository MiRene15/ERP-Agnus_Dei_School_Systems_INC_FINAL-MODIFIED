<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rename classes.semester → classes.term (only if semester exists)
        $classHasSemester = Schema::hasColumn('classes', 'semester');
        if ($classHasSemester) {
            Schema::table('classes', function (Blueprint $table) {
                $table->renameColumn('semester', 'term');
            });
        }

        // 2. fee_schedules: rename semester → term or add term if missing
        $feeHasSemester = Schema::hasColumn('fee_schedules', 'semester');
        $feeHasTerm = Schema::hasColumn('fee_schedules', 'term');

        if ($feeHasSemester && !$feeHasTerm) {
            Schema::table('fee_schedules', function (Blueprint $table) {
                $table->string('term', 20)->after('grade_level')->nullable();
            });

            DB::table('fee_schedules')->update([
                'term' => DB::raw("CASE semester
                    WHEN '1st Semester' THEN '1st Term'
                    WHEN '2nd Semester' THEN '2nd Term'
                    WHEN '3rd Semester' THEN '3rd Term'
                    ELSE semester
                END"),
            ]);

            Schema::table('fee_schedules', function (Blueprint $table) {
                $table->dropColumn('semester');
            });
        }

        // 3. Add scholarship boolean to students (only if not exists)
        if (!Schema::hasColumn('students', 'scholarship')) {
            Schema::table('students', function (Blueprint $table) {
                $table->boolean('scholarship')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'scholarship')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('scholarship');
            });
        }

        $feeHasTerm = Schema::hasColumn('fee_schedules', 'term');
        $feeHasSemester = Schema::hasColumn('fee_schedules', 'semester');

        if ($feeHasTerm && !$feeHasSemester) {
            Schema::table('fee_schedules', function (Blueprint $table) {
                $table->string('semester', 20)->after('grade_level')->nullable();
            });

            DB::table('fee_schedules')->update([
                'semester' => DB::raw("CASE term
                    WHEN '1st Term' THEN '1st Semester'
                    WHEN '2nd Term' THEN '2nd Semester'
                    WHEN '3rd Term' THEN '3rd Semester'
                    ELSE term
                END"),
            ]);

            Schema::table('fee_schedules', function (Blueprint $table) {
                $table->dropColumn('term');
            });
        }

        if (Schema::hasColumn('classes', 'term') && !Schema::hasColumn('classes', 'semester')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->renameColumn('term', 'semester');
            });
        }
    }
};
