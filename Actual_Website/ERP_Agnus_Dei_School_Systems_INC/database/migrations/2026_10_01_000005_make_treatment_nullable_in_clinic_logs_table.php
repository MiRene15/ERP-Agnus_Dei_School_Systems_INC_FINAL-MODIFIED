<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The nurse form allows empty treatment, but the column was NOT NULL —
// submitting without it crashed with a SQLSTATE not-null violation.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_logs', function (Blueprint $table) {
            $table->text('treatment')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clinic_logs', function (Blueprint $table) {
            $table->text('treatment')->nullable(false)->change();
        });
    }
};
