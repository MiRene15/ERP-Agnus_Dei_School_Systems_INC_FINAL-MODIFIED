<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('ar_number', 30)->nullable()->after('receipt_number');
            $table->string('receipt_file_path', 500)->nullable()->after('ar_number');
        });

        Schema::table('fee_schedules', function (Blueprint $table) {
            $table->text('misc_fee_items')->nullable()->after('misc_fee');
        });

        Schema::table('settings', function (Blueprint $table) {
            // AR number sequence tracking
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['ar_number', 'receipt_file_path']);
        });

        Schema::table('fee_schedules', function (Blueprint $table) {
            $table->dropColumn('misc_fee_items');
        });
    }
};
