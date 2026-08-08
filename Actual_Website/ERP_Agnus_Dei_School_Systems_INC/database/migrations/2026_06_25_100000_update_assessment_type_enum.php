<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("UPDATE assessments SET type = 'Semestral Assessment' WHERE type = 'Quarterly Assessment'");

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE assessments DROP CONSTRAINT IF EXISTS assessments_type_check");
            DB::statement("ALTER TABLE assessments ADD CONSTRAINT assessments_type_check CHECK (type IN ('Written Work', 'Performance Task', 'Semestral Assessment'))");
            return;
        }

        DB::statement("ALTER TABLE assessments MODIFY COLUMN type ENUM('Written Work', 'Performance Task', 'Semestral Assessment')");
    }

    public function down()
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE assessments DROP CONSTRAINT IF EXISTS assessments_type_check");
            DB::statement("ALTER TABLE assessments ADD CONSTRAINT assessments_type_check CHECK (type IN ('Written Work', 'Performance Task', 'Quarterly Assessment'))");
            return;
        }

        DB::statement("ALTER TABLE assessments MODIFY COLUMN type ENUM('Written Work', 'Performance Task', 'Quarterly Assessment')");
    }
};
