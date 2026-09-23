<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->foreignId('replacement_of')->nullable()->after('id')->constrained('books')->nullOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['replacement_of']);
            $table->dropColumn('replacement_of');
        });
    }
};
