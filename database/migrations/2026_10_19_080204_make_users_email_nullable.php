<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('users')->whereNull('email')->exists()) {
            throw new LogicException('Cannot require institutional emails while users without an email exist.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
