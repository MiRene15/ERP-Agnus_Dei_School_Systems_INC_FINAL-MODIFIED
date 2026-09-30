<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 2d (role reform): whole-school announcements need Directress awareness.
// Null = not yet seen; reset to null whenever the Principal edits the announcement.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->timestamp('directress_seen_at')->nullable()->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('directress_seen_at');
        });
    }
};
