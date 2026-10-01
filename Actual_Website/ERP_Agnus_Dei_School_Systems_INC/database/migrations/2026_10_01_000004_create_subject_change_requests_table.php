<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Subject-change approvals: Registrar stages every create/update/delete (incl.
// CSV import rows) as a request; Principal approves (applies) or rejects.
// Nothing touches the live subjects table without Principal approval.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_change_requests', function (Blueprint $table) {
            $table->id();
            $table->string('action', 20); // create | update | delete
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->json('payload')->nullable(); // subject_code, name, grade_level, category
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_change_requests');
    }
};
