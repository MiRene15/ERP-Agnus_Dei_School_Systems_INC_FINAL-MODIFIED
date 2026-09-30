<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 2a (role reform): two-step discount approvals.
// Cashier/Registrar requests with proof -> Directress approves/rejects -> Cashier applies.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_ledger_id')->constrained('student_ledgers')->cascadeOnDelete();
            $table->string('discount_type', 20); // honor | sibling | esc | other
            $table->decimal('discount_amount', 10, 2);
            $table->text('proof_details'); // reference to supporting proof (ESC cert no., honor list, sibling record, ...)
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending | approved | rejected | applied
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_requests');
    }
};
