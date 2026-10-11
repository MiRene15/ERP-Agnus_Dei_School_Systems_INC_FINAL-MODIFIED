<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            // Proof of Cashier downpayment (spec:
            // enrollment-payment-gating-payment-access.md). A paid file is
            // Pending carrying these — the receipt badge, the Forward
            // guard, and the refund-drop-back all read them. Who/when lives
            // in the audit log, not here.
            $table->string('downpayment_receipt')->nullable()->after('review_note');
            $table->timestamp('downpayment_paid_at')->nullable()->after('downpayment_receipt');
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn(['downpayment_receipt', 'downpayment_paid_at']);
        });
    }
};
