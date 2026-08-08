<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE admissions DROP CONSTRAINT IF EXISTS admissions_application_type_check");
            DB::statement("ALTER TABLE admissions ADD CONSTRAINT admissions_application_type_check CHECK (application_type IN ('New', 'Old', 'Transferee', 'Honor', 'Sibling'))");
            return;
        }

        DB::statement("ALTER TABLE admissions MODIFY COLUMN application_type ENUM('New', 'Old', 'Transferee', 'Honor', 'Sibling')");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE admissions DROP CONSTRAINT IF EXISTS admissions_application_type_check");
            DB::statement("ALTER TABLE admissions ADD CONSTRAINT admissions_application_type_check CHECK (application_type IN ('New', 'Old', 'Transferee'))");
            return;
        }

        DB::statement("ALTER TABLE admissions MODIFY COLUMN application_type ENUM('New', 'Old', 'Transferee')");
    }
};
