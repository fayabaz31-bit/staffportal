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
        Schema::create('payslip_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('payroll_period', 7);
            $table->string('original_filename', 255);
            $table->string('status', 20)->default('staged');
            $table->unsignedInteger('total_files')->default(0);
            $table->unsignedInteger('matched_files')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('reconciliation_summary')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('withdrawn_at')->nullable();
            $table->text('withdrawal_reason')->nullable();
            $table->timestamps();

            $table->index(['payroll_period', 'status']);
            $table->index(['uploaded_by', 'created_at']);
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_batch_id')->constrained('payslip_batches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('staff_number', 50);
            $table->string('payroll_period', 7);
            $table->string('original_filename', 255);
            $table->string('storage_path', 255);
            $table->string('file_hash', 64);
            $table->unsignedBigInteger('file_size');
            $table->string('status', 20)->default('published');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('withdrawal_reason')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'payroll_period']);
            $table->index(['payslip_batch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payslip_batches');
    }
};
