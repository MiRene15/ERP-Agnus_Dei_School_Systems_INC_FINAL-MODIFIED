<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Pre-admission accounts save before gender is known (spec:
            // admission-gender-required.md) — inquiry must succeed with
            // gender empty. Widen to 20 for "Prefer not to say" (16).
            $table->string('gender', 20)->nullable()->change();
        });

        if (! Schema::hasColumn('students', 'gender_detail')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('gender_detail', 100)->nullable()->after('gender');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'gender_detail')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('gender_detail');
            });
        }
    }
};
