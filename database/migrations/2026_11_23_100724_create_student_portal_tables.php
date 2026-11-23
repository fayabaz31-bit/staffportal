<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('student_number')->unique();
            $table->string('password');
            $table->string('status')->default('active');
            $table->string('programme');
            $table->string('session');
            $table->string('cohort')->nullable();
            $table->string('institution')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('placement_result')->nullable();
            $table->boolean('email_notices')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('student_courses', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('title');
            $table->string('programme');
            $table->string('session');
            $table->unsignedTinyInteger('credits')->default(1);
            $table->boolean('registration_open')->default(false);
            $table->string('timetable')->nullable();
            $table->timestamps();
            $table->unique(['code', 'programme', 'session']);
        });
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_course_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('submitted');
            $table->decimal('score', 5, 2)->nullable();
            $table->string('grade')->nullable();
            $table->timestamp('result_published_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'student_course_id']);
        });
        Schema::create('student_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->uuid('reference')->unique();
            $table->string('description');
            $table->unsignedBigInteger('amount_kobo');
            $table->string('session');
            $table->date('due_date')->nullable();
            $table->string('rrr')->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('path');
            $table->string('original_name');
            $table->string('status')->default('submitted');
            $table->boolean('issued_by_staff')->default(false);
            $table->timestamps();
        });
        Schema::create('student_notices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('programme')->nullable();
            $table->string('session')->nullable();
            $table->date('event_date')->nullable();
            $table->timestamp('published_at');
            $table->timestamps();
        });
        Schema::create('student_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('subject');
            $table->text('body');
            $table->json('proposed_changes')->nullable();
            $table->string('status')->default('open');
            $table->text('decision_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('student_request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('from_staff')->default(false);
            $table->text('body');
            $table->timestamps();
        });
        Schema::create('student_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['student_password_reset_tokens', 'student_request_messages', 'student_requests', 'student_notices', 'student_documents', 'student_invoices', 'student_enrollments', 'student_courses', 'students'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
