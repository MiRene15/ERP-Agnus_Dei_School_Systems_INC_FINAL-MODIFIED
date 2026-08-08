<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE student_ledgers DROP CONSTRAINT IF EXISTS student_ledgers_payment_plan_check");
            DB::statement("ALTER TABLE student_ledgers ALTER COLUMN payment_plan TYPE VARCHAR(20)");
            DB::statement("ALTER TABLE student_ledgers ALTER COLUMN payment_plan SET DEFAULT 'installment'");
            DB::statement("ALTER TABLE student_ledgers ALTER COLUMN payment_plan SET NOT NULL");
            DB::statement("UPDATE student_ledgers SET payment_plan = 'installment' WHERE payment_plan NOT IN ('installment', 'full')");
            return;
        }

        DB::statement("ALTER TABLE student_ledgers MODIFY COLUMN payment_plan VARCHAR(20) NOT NULL DEFAULT 'installment'");
        DB::statement("UPDATE student_ledgers SET payment_plan = 'installment' WHERE payment_plan NOT IN ('installment', 'full')");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE student_ledgers ALTER COLUMN payment_plan TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE student_ledgers DROP CONSTRAINT IF EXISTS student_ledgers_payment_plan_check");
            DB::statement("ALTER TABLE student_ledgers ALTER COLUMN payment_plan SET DEFAULT 'Plan A'");
            DB::statement("ALTER TABLE student_ledgers ALTER COLUMN payment_plan SET NOT NULL");
            DB::statement("ALTER TABLE student_ledgers ADD CONSTRAINT student_ledgers_payment_plan_check CHECK (payment_plan IN ('Plan A', 'Plan B', 'Plan C'))");
            return;
        }

        DB::statement("ALTER TABLE student_ledgers MODIFY COLUMN payment_plan ENUM('Plan A', 'Plan B', 'Plan C') NOT NULL DEFAULT 'Plan A'");
    }
};
