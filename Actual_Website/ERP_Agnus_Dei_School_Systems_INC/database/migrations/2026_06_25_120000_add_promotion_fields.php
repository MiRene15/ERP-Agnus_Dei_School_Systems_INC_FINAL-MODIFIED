<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('student_ledgers', function (Blueprint $table) {
            $table->decimal('carried_over_balance', 10, 2)->default(0)->after('balance');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('promoted_to_enrollment_id')->nullable()->constrained('enrollments');
        });
    }

    public function down()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['promoted_to_enrollment_id']);
            $table->dropColumn('promoted_to_enrollment_id');
        });

        Schema::table('student_ledgers', function (Blueprint $table) {
            $table->dropColumn('carried_over_balance');
        });
    }
};
