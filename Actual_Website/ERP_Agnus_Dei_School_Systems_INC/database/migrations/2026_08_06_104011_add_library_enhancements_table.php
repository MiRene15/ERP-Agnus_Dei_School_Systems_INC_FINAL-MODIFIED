<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_transactions', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable()->after('status');
            $table->string('condition_at_borrow', 20)->default('Good')->after('status');
            $table->string('condition_at_return', 20)->nullable()->after('condition_at_borrow');
            $table->decimal('late_fee', 10, 2)->default(0)->after('condition_at_return');
            $table->decimal('damage_fee', 10, 2)->default(0)->after('late_fee');
            $table->decimal('lost_fee', 10, 2)->default(0)->after('damage_fee');
            $table->decimal('total_fees', 10, 2)->default(0)->after('lost_fee');
            $table->text('damage_notes')->nullable()->after('total_fees');
            $table->boolean('fees_assessed')->default(false)->after('damage_notes');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->string('serial_number', 50)->nullable()->after('isbn');
            $table->boolean('is_active')->default(true)->after('available_quantity');
            $table->text('inactive_reason')->nullable()->after('is_active');
            $table->timestamp('inactive_at')->nullable()->after('inactive_reason');
            $table->unsignedBigInteger('deactivated_by')->nullable()->after('inactive_at');
            $table->foreign('deactivated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('library_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'returned_at', 'condition_at_borrow', 'condition_at_return',
                'late_fee', 'damage_fee', 'lost_fee', 'total_fees',
                'damage_notes', 'fees_assessed',
            ]);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn([
                'serial_number', 'is_active', 'inactive_reason',
                'inactive_at', 'deactivated_by',
            ]);
        });
    }
};
