<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('place_of_birth')->nullable()->after('date_of_birth');
            $table->string('citizenship')->nullable()->after('place_of_birth');
            $table->string('religion')->nullable()->after('citizenship');
            $table->text('permanent_address')->nullable()->after('religion');
            $table->text('current_address')->nullable()->after('permanent_address');
            $table->string('contact_number')->nullable()->after('current_address');
            $table->string('father_name')->nullable()->after('contact_number');
            $table->string('father_occupation')->nullable()->after('father_name');
            $table->string('mother_name')->nullable()->after('father_occupation');
            $table->string('mother_occupation')->nullable()->after('mother_name');
            $table->string('guardian_name')->nullable()->after('mother_occupation');
            $table->string('guardian_contact')->nullable()->after('guardian_name');
            $table->string('emergency_contact_name')->nullable()->after('guardian_contact');
            $table->string('emergency_contact_number')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_number');
            $table->string('previous_school')->nullable()->after('emergency_contact_relationship');
            $table->text('previous_school_address')->nullable()->after('previous_school');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'place_of_birth', 'citizenship', 'religion',
                'permanent_address', 'current_address', 'contact_number',
                'father_name', 'father_occupation', 'mother_name', 'mother_occupation',
                'guardian_name', 'guardian_contact',
                'emergency_contact_name', 'emergency_contact_number', 'emergency_contact_relationship',
                'previous_school', 'previous_school_address',
            ]);
        });
    }
};
