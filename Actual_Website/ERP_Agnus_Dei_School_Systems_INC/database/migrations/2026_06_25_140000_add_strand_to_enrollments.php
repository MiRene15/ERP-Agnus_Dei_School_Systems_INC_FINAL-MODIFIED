<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('strand')->nullable()->after('school_year');
        });
    }

    public function down()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('strand');
        });
    }
};
