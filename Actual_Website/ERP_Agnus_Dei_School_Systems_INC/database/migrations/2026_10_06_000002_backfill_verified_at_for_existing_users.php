<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Applicant email reliability (spec: applicant-email-reliability.md).
// One-time backfill: accounts created before verification existed are treated
// as verified so switching enforcement on never locks out staff or current
// students. Only accounts created afterwards start unverified.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Intentionally irreversible: re-nulling would lock out real users.
    }
};
