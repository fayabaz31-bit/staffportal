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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('designation')->nullable();
            $table->string('employment_status', 32)->default('unknown');
            $table->string('phone_number', 40)->nullable();
            $table->string('personal_email')->nullable();
            $table->text('residential_address')->nullable();
            $table->timestamps();
        });

        Schema::create('profile_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('field_key', 32);
            $table->text('current_value')->nullable();
            $table->text('proposed_value');
            $table->text('reason');
            $table->string('status', 16)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_note')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'field_key', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_change_requests');
        Schema::dropIfExists('staff_profiles');
        Schema::dropIfExists('departments');
    }
};
