<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configurable approval workflow engine.
     *
     * Definitions are versioned. An instance pins the version it started on, so
     * editing a definition never silently rewrites the chain for work already
     * in progress (Master Prompt §7/§17).
     */
    public function up(): void
    {
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->string('document_type', 60);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->boolean('is_published')->default(false);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->unique(['workflow_definition_id', 'version'], 'workflow_versions_definition_version_uq');
        });

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_version_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('code', 60);
            $table->string('name');
            $table->string('action_required', 24)->default('review');
            $table->string('approver_type', 24)->default('duty');
            $table->string('approver_role', 60)->nullable();
            $table->string('approver_duty', 80)->nullable();
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_mandatory')->default(true);
            $table->unsignedSmallInteger('sla_hours')->nullable();
            $table->timestamps();

            $table->unique(['workflow_version_id', 'sequence'], 'workflow_steps_version_sequence_uq');
            $table->index(['workflow_version_id', 'code'], 'workflow_steps_version_code_idx');
        });

        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained()->restrictOnDelete();
            $table->foreignId('workflow_version_id')->constrained()->restrictOnDelete();
            $table->string('document_type', 60);
            $table->unsignedBigInteger('document_id');
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->string('status', 32)->default('in_progress');
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'document_id'], 'workflow_instances_document_idx');
            $table->index(['status', 'current_step_id'], 'workflow_instances_status_step_idx');
        });

        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->text('comments')->nullable();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['workflow_instance_id', 'created_at'], 'workflow_actions_instance_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_actions');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('workflow_definitions');
    }
};
