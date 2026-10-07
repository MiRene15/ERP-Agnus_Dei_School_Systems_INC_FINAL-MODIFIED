<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Hotfix: submission references are route-scoped by design — the same client
// marker is recorded once per layer (web guard row, then mail row). The
// original reference-only unique key made the second layer's insert collide,
// failing every mail send that carried a marker. Scope uniqueness to
// (reference, route) so each layer owns its row; true replays (same
// reference AND route) still collide and are still treated as repeats.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('idempotency_keys', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->unique(['reference', 'route']);
        });
    }

    public function down(): void
    {
        Schema::table('idempotency_keys', function (Blueprint $table) {
            $table->dropUnique(['reference', 'route']);
            $table->unique('reference');
        });
    }
};
