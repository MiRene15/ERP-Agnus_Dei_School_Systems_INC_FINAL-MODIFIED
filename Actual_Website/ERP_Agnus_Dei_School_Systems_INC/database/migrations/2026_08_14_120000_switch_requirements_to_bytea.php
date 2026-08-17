<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirements', function (Blueprint $table) {
            $table->binary('file_content')->nullable()->after('document_type');
            $table->string('mime_type')->nullable()->after('file_content');
        });

        Schema::table('requirements', function (Blueprint $table) {
            $table->dropColumn(['file_path']);
        });
    }

    public function down(): void
    {
        Schema::table('requirements', function (Blueprint $table) {
            $table->string('file_path', 500)->nullable()->after('document_type');
            $table->dropColumn(['file_content', 'mime_type']);
        });
    }
};
