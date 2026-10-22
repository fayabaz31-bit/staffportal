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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_reference', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('unit', 30); // 'registry', 'bursary', 'ict'
            $table->string('category', 60);
            $table->string('subject', 200);
            $table->text('description');
            $table->string('priority', 20)->default('standard'); // 'low', 'standard', 'high', 'urgent'
            $table->string('status', 30)->default('open'); // 'open', 'in_progress', 'escalated', 'resolved', 'closed'
            $table->boolean('is_confidential')->default(false);
            $table->string('attachment_path', 255)->nullable();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['unit', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['is_confidential', 'unit']);
            $table->index(['status', 'priority']);
        });

        Schema::create('service_request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->string('attachment_path', 255)->nullable();
            $table->timestamps();

            $table->index(['service_request_id', 'is_internal']);
        });

        Schema::create('service_request_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['service_request_id', 'created_at']);
        });

        Schema::create('report_export_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->string('report_type', 60);
            $table->string('format', 20)->default('csv');
            $table->json('filters')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_id', 'created_at']);
            $table->index(['report_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_export_audits');
        Schema::dropIfExists('service_request_status_logs');
        Schema::dropIfExists('service_request_messages');
        Schema::dropIfExists('service_requests');
    }
};
