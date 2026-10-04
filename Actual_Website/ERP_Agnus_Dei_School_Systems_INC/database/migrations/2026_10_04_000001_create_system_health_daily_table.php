<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// System Health history for 14-day line graphs (spec: it-search-health-dashboard.md).
// One row per day; counts only, never search words or PII.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_health_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('abuse_hits')->default(0);
            $table->unsignedInteger('slow_total')->default(0);
            $table->unsignedInteger('login_failed')->default(0);
            $table->unsignedInteger('login_locked')->default(0);
            $table->unsignedInteger('new_accounts')->default(0);
            $table->boolean('uptime_ok')->default(true);
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_health_daily');
    }
};
