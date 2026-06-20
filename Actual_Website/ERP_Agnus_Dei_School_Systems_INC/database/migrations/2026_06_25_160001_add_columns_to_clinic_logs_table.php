<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clinic_logs', function (Blueprint $table) {
            $table->text('complaint')->nullable()->after('symptoms');
            $table->text('diagnosis')->nullable()->after('complaint');
            $table->text('notes')->nullable()->after('treatment');
            $table->string('referred_to')->nullable()->after('notes');
            $table->date('visit_date')->nullable()->after('referred_to');
        });
    }

    public function down()
    {
        Schema::table('clinic_logs', function (Blueprint $table) {
            $table->dropColumn(['complaint', 'diagnosis', 'notes', 'referred_to', 'visit_date']);
        });
    }
};
