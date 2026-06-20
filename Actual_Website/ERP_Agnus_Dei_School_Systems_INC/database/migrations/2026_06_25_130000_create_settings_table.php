<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            'key' => 'active_school_year',
            'value' => date('Y') . '-' . (date('Y') + 1),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
};
