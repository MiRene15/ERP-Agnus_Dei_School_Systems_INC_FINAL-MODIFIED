<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 3 (holds engine): open clinic cases (e.g. pending referral) raise a
// clearance Hold until the nurse closes them. Existing logs stay closed.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_logs', function (Blueprint $table) {
            $table->boolean('is_open')->default(false)->after('referred_to');
            $table->timestamp('closed_at')->nullable()->after('is_open');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_logs', function (Blueprint $table) {
            $table->dropColumn(['is_open', 'closed_at']);
        });
    }
};
