<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds a unique Application Number (ADM-YYYY-XXXXX) to the admissions table.
     * This is separate from the Student Number (YYYY-XXXXX) assigned at approval.
     */
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->string('application_number')->unique()->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropUnique(['application_number']);
            $table->dropColumn('application_number');
        });
    }
};
