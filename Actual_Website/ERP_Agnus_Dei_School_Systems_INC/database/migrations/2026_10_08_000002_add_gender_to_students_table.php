<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'gender')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('gender', 10)->nullable();
            });
        }

        DB::table('students')
            ->whereNull('gender')
            ->update(['gender' => DB::raw("CASE WHEN random() < 0.5 THEN 'Female' ELSE 'Male' END")]);

        Schema::table('students', function (Blueprint $table) {
            $table->string('gender', 10)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
