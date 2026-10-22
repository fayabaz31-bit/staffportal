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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('default_days_per_year')->default(30);
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('requires_supervisor_review')->default(true);
            $table->boolean('requires_hr_verification')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('allocated_days')->default(0);
            $table->unsignedInteger('used_days')->default(0);
            $table->unsignedInteger('pending_days')->default(0);
            $table->boolean('is_policy_configured')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'leave_type_id', 'year']);
            $table->index(['user_id', 'year']);
        });

        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('days_requested');
            $table->text('reason');
            $table->string('attachment_path', 255)->nullable();
            $table->string('status', 30)->default('submitted');
            $table->string('current_stage', 30)->default('supervisor');
            $table->foreignId('assigned_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('assigned_approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('return_reason')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('concurrency_version')->default(1);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['current_stage', 'status']);
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('leave_approval_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_application_id')->constrained('leave_applications')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->string('stage', 30);
            $table->string('action', 30);
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['leave_application_id', 'created_at']);
        });

        Schema::create('official_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('leave_application_id')->nullable()->constrained('leave_applications')->nullOnDelete();
            $table->string('reference_number', 100)->unique();
            $table->string('document_type', 50)->default('leave_approval');
            $table->string('subject', 255);
            $table->longText('body_html');
            $table->string('storage_path', 255);
            $table->string('file_hash', 64);
            $table->unsignedBigInteger('file_size');
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index(['user_id', 'issued_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('official_letters');
        Schema::dropIfExists('leave_approval_actions');
        Schema::dropIfExists('leave_applications');
        Schema::dropIfExists('leave_entitlements');
        Schema::dropIfExists('leave_types');
    }
};
