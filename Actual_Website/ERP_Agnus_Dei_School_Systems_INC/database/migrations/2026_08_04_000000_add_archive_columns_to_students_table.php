<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('archive_action')->nullable()->after('status');
            $table->text('archive_reason')->nullable()->after('archive_action');
            $table->timestamp('archived_at')->nullable()->after('archive_reason');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['archive_action', 'archive_reason', 'archived_at']);
        });
    }
};
