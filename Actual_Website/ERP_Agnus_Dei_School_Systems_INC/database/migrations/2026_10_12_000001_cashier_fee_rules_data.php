<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Spec: cashier-fee-rules.md — Slice 1 (data).
// SY-scoped fees + term/installment lines + shared tuition+library receipt link.
// Posted assessments stay immutable: later fee edits only affect new assessments (enforced in service, Slice 2).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_schedules', function (Blueprint $table) {
            $table->index('school_year', 'fee_schedules_school_year_index');
            $table->unique(['grade_level', 'term', 'school_year'], 'fee_schedules_grade_term_year_unique');
        });

        Schema::table('student_ledgers', function (Blueprint $table) {
            $table->string('school_year', 20)->nullable()->after('student_id');
            $table->string('assessment_path', 20)->nullable()->after('payment_plan');
            $table->decimal('enrollment_fee', 10, 2)->default(1500)->after('total_assessed');
            $table->decimal('tuition_assessed', 10, 2)->default(0)->after('enrollment_fee');
            $table->unsignedSmallInteger('installment_count')->default(10)->after('tuition_assessed');
            $table->index('school_year', 'student_ledgers_school_year_index');
        });

        Schema::create('ledger_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_id')->constrained('student_ledgers')->cascadeOnDelete();
            $table->string('school_year', 20);
            $table->string('kind', 20); // enrollment | tuition
            $table->string('label', 80); // e.g. "Enrollment Fee", "Month 3"
            $table->string('term_label', 40)->nullable();
            $table->unsignedSmallInteger('due_month')->nullable(); // 1-10 for installments, null for enrollment/full
            $table->date('due_date')->nullable();
            $table->decimal('amount_assessed', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('status', 20)->default('unpaid'); // unpaid | partial | paid
            $table->timestamps();

            $table->unique(['ledger_id', 'label'], 'ledger_lines_ledger_label_unique');
            $table->index(['ledger_id', 'due_month'], 'ledger_lines_ledger_month_index');
            $table->index('school_year', 'ledger_lines_school_year_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('tuition_amount', 10, 2)->nullable()->after('amount_paid');
            $table->decimal('library_amount', 10, 2)->default(0)->after('tuition_amount');
        });

        Schema::table('library_transactions', function (Blueprint $table) {
            $table->foreignId('paid_payment_id')->nullable()->after('fees_assessed')->constrained('payments')->nullOnDelete();
            $table->timestamp('paid_at')->nullable()->after('paid_payment_id');
            $table->index('paid_payment_id', 'library_transactions_paid_payment_index');
        });

        // One due, one receipt: the unique on library_transaction_id makes a
        // double-post (Library + Cashier, or two Cashier receipts) fail at the DB level.
        Schema::create('library_transaction_payment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('library_transaction_id')->constrained('library_transactions')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->unique('library_transaction_id', 'lib_txn_pay_lib_txn_unique');
            $table->index('payment_id', 'lib_txn_pay_payment_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_transaction_payment');

        Schema::table('library_transactions', function (Blueprint $table) {
            $table->dropIndexIfExists('library_transactions_paid_payment_index');
            $table->dropConstrainedForeignId('paid_payment_id');
            $table->dropColumn('paid_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['tuition_amount', 'library_amount']);
        });

        Schema::dropIfExists('ledger_lines');

        Schema::table('student_ledgers', function (Blueprint $table) {
            $table->dropIndexIfExists('student_ledgers_school_year_index');
            $table->dropColumn(['school_year', 'assessment_path', 'enrollment_fee', 'tuition_assessed', 'installment_count']);
        });

        Schema::table('fee_schedules', function (Blueprint $table) {
            $table->dropUnique('fee_schedules_grade_term_year_unique');
            $table->dropIndexIfExists('fee_schedules_school_year_index');
        });
    }
};
