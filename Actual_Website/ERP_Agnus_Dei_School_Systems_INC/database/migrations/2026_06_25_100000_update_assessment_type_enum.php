<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("UPDATE assessments SET type = 'Semestral Assessment' WHERE type = 'Quarterly Assessment'");
        DB::statement("ALTER TABLE assessments MODIFY COLUMN type ENUM('Written Work', 'Performance Task', 'Semestral Assessment')");
    }

    public function down()
    {
        DB::statement("ALTER TABLE assessments MODIFY COLUMN type ENUM('Written Work', 'Performance Task', 'Quarterly Assessment')");
    }
};
