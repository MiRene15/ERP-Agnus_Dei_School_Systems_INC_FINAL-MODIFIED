<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE student_ledgers MODIFY COLUMN payment_plan VARCHAR(20) NOT NULL DEFAULT 'installment'");
        DB::statement("UPDATE student_ledgers SET payment_plan = 'installment' WHERE payment_plan NOT IN ('installment', 'full')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE student_ledgers MODIFY COLUMN payment_plan ENUM('Plan A', 'Plan B', 'Plan C') NOT NULL DEFAULT 'Plan A'");
    }
};
