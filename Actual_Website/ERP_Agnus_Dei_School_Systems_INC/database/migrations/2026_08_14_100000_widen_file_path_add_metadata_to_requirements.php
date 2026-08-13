<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirements', function (Blueprint $table) {
            $table->string('file_path', 500)->change();
            $table->string('original_filename')->nullable()->after('file_path');
            $table->unsignedInteger('file_size')->nullable()->after('original_filename');
        });
    }

    public function down(): void
    {
        Schema::table('requirements', function (Blueprint $table) {
            $table->string('file_path')->change();
            $table->dropColumn(['original_filename', 'file_size']);
        });
    }
};
