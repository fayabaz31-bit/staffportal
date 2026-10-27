<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->string('state_of_origin', 64)->nullable()->after('marital_status');
            $table->text('educational_qualifications')->nullable()->after('state_of_origin');
            $table->text('professional_qualifications')->nullable()->after('educational_qualifications');
            $table->string('pencom_number', 64)->nullable()->after('professional_qualifications');
            $table->string('pfa_name', 128)->nullable()->after('pencom_number');
            $table->string('passport_path', 255)->nullable()->after('pfa_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'state_of_origin',
                'educational_qualifications',
                'professional_qualifications',
                'pencom_number',
                'pfa_name',
                'passport_path',
            ]);
        });
    }
};
