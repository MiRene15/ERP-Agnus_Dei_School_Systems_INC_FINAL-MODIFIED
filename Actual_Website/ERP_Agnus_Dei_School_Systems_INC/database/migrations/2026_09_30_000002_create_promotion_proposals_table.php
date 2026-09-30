<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 2b (role reform): promotion handoff.
// Registrar proposes -> Principal approves -> Directress signs off (executes).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->string('action', 20); // promote | retain | graduate | transfer | dropped
            $table->string('school_year', 20);
            $table->text('reason')->nullable(); // required for transfer / dropped
            $table->string('status', 30)->default('proposed'); // proposed | principal_approved | rejected | directress_approved | executed
            $table->foreignId('proposed_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('principal_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('principal_at')->nullable();
            $table->foreignId('directress_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('directress_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_proposals');
    }
};
