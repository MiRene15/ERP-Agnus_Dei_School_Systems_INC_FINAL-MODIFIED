<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_ledgers', function (Blueprint $table) {
            $table->timestamp('it_confirmed_at')->nullable()->after('clearance_status');
        });
    }

    public function down(): void
    {
        Schema::table('student_ledgers', function (Blueprint $table) {
            $table->dropColumn('it_confirmed_at');
        });
    }
};
