<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registry correspondence: categories, templates, documents, versions and
     * private attachments.
     *
     * Approval, issuance and delivery are deliberately separate columns: a
     * letter is not "sent" merely because it was approved or generated
     * (Master Prompt §8).
     */
    public function up(): void
    {
        Schema::create('correspondence_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('requires_registrar_approval')->default(true);
            $table->boolean('requires_director_approval')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('correspondence_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correspondence_category_id')->nullable()->constrained('correspondence_categories')->nullOnDelete();
            $table->string('name');
            $table->string('subject_template');
            $table->longText('body_template');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('correspondence_documents', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 80)->unique();
            $table->foreignId('correspondence_category_id')->constrained('correspondence_categories')->restrictOnDelete();
            $table->foreignId('correspondence_template_id')->nullable()->constrained('correspondence_templates')->nullOnDelete();
            $table->string('subject');
            $table->foreignId('originator_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name')->nullable();
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('current_version')->default(1);
            $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('issued_at')->nullable();
            $table->foreignId('superseded_by')->nullable()->constrained('correspondence_documents')->nullOnDelete();
            $table->dateTime('withdrawn_at')->nullable();
            $table->string('delivery_status', 24)->default('not_sent');
            $table->string('storage_path')->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'correspondence_status_created_idx');
            $table->index('originator_id', 'correspondence_originator_idx');
            $table->index('recipient_user_id', 'correspondence_recipient_idx');
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correspondence_document_id')->constrained('correspondence_documents')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('body_html');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['correspondence_document_id', 'version'], 'document_versions_document_version_uq');
        });

        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correspondence_document_id')->constrained('correspondence_documents')->cascadeOnDelete();
            $table->foreignId('document_version_id')->nullable()->constrained('document_versions')->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('original_name');
            $table->string('storage_path')->unique();
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->string('sha256', 64);
            $table->timestamp('created_at')->nullable();

            $table->index('correspondence_document_id', 'document_attachments_document_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_attachments');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('correspondence_documents');
        Schema::dropIfExists('correspondence_templates');
        Schema::dropIfExists('correspondence_categories');
    }
};
