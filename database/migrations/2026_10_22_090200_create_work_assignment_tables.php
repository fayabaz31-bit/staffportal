<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assigned work (a "duty" in institutional language).
     *
     * The existing `duties` table is the *authorisation catalogue* — a named
     * "desk" that permits an action. This table is the piece of work an
     * officer is asked to do. Keeping them separate means a duty assignment can
     * carry a deadline, status history and attachments without changing how
     * authorisation is expressed.
     */
    public function up(): void
    {
        Schema::create('work_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 60)->unique();
            $table->string('title');
            $table->text('instructions');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organisational_unit_id')->nullable()->constrained('organisational_units')->nullOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->restrictOnDelete();
            $table->string('priority', 16)->default('normal');
            $table->string('status', 32)->default('assigned');
            $table->string('confidentiality', 16)->default('internal');
            $table->text('required_deliverables')->nullable();
            $table->string('related_document_type', 40)->nullable();
            $table->unsignedBigInteger('related_document_id')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('acknowledged_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('last_returned_at')->nullable();
            $table->unsignedInteger('concurrency_version')->default(0);
            $table->timestamps();

            $table->index(['assigned_to', 'status'], 'assignments_assignee_status_idx');
            $table->index(['assigned_by', 'status'], 'assignments_assigner_status_idx');
            $table->index(['status', 'due_at'], 'assignments_status_due_idx');
            $table->index(['related_document_type', 'related_document_id'], 'assignments_related_idx');
        });

        Schema::create('assignment_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->string('kind', 24)->default('comment');
            $table->text('body');
            $table->timestamps();

            $table->index(['work_assignment_id', 'created_at'], 'assignment_comments_assignment_idx');
        });

        Schema::create('assignment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['work_assignment_id', 'created_at'], 'assignment_events_assignment_idx');
        });

        Schema::create('assignment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('original_name');
            $table->string('storage_path')->unique();
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->string('sha256', 64);
            $table->timestamp('created_at')->nullable();

            $table->index('work_assignment_id', 'assignment_attachments_assignment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_attachments');
        Schema::dropIfExists('assignment_events');
        Schema::dropIfExists('assignment_comments');
        Schema::dropIfExists('work_assignments');
    }
};
