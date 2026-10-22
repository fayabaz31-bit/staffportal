<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extra personnel detail captured by the Registry import and manual entry,
     * plus a display order so the staff directory can be grouped by department
     * in the institution's official sequence.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('name');
        });

        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->string('gender', 16)->nullable()->after('designation');
            $table->string('grade_level', 32)->nullable()->after('gender');
            $table->string('unit', 128)->nullable()->after('grade_level');
            $table->date('date_of_first_appointment')->nullable()->after('unit');
            $table->date('date_of_birth')->nullable()->after('date_of_first_appointment');
            $table->string('marital_status', 24)->nullable()->after('date_of_birth');
            $table->string('next_of_kin', 128)->nullable()->after('marital_status');
            $table->string('next_of_kin_phone', 40)->nullable()->after('next_of_kin');
        });
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'grade_level',
                'unit',
                'date_of_first_appointment',
                'date_of_birth',
                'marital_status',
                'next_of_kin',
                'next_of_kin_phone',
            ]);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
