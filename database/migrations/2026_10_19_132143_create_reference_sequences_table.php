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
        Schema::create('reference_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 60);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('next_value')->default(0);
            $table->timestamps();

            $table->unique(['scope', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_sequences');
    }
};
