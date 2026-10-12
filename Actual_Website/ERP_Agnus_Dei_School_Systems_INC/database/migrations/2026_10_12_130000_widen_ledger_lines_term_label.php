<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Spec: downpayment-term-label-overflow.md — bundled month text
        // (Incl. Tuition + Books + Uniform + ID + Miscellaneous, ~95 chars)
        // overflows term_label varchar(40) and rejects every installment
        // downpayment insert. Widen to 255, keep full text, no data change.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ledger_lines ALTER COLUMN term_label TYPE VARCHAR(255)');

            return;
        }

        DB::statement('ALTER TABLE ledger_lines MODIFY COLUMN term_label VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ledger_lines ALTER COLUMN term_label TYPE VARCHAR(40)');

            return;
        }

        DB::statement('ALTER TABLE ledger_lines MODIFY COLUMN term_label VARCHAR(40) NULL');
    }
};
