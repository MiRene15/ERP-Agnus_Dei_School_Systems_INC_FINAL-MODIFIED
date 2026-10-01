<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 4a: attendance tracking (did not exist). One row per class x enrollment x date.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->date('marked_on');
            $table->string('status', 20)->default('present'); // present | absent | late | excused
            $table->foreignId('marked_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['class_id', 'enrollment_id', 'marked_on']);
            $table->index(['class_id', 'marked_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
