<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fee_schedules', function (Blueprint $table) {
            $table->string('term', 20)->after('grade_level');
        });
    }

    public function down()
    {
        Schema::table('fee_schedules', function (Blueprint $table) {
            $table->dropColumn('term');
        });
    }
};
