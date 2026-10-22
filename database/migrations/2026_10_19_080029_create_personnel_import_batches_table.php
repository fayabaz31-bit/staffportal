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
        Schema::create('personnel_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('original_filename', 255);
            $table->string('status', 20)->default('preview');
            $table->unsignedSmallInteger('row_count')->default(0);
            $table->unsignedSmallInteger('valid_row_count')->default(0);
            $table->unsignedSmallInteger('error_row_count')->default(0);
            $table->json('preview_rows');
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();
            $table->index(['uploaded_by', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_import_batches');
    }
};
