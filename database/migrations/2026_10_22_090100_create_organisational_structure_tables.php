<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Organisational units, reporting lines and time-bound delegations.
     *
     * The Registry is configurable: units are rows, not code, and a reporting
     * line carries effective dates so transfers and acting appointments keep
     * their history (Master Prompt §5/§11).
     */
    public function up(): void
    {
        Schema::create('organisational_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('organisational_units')->nullOnDelete();
            $table->foreignId('head_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['department_id', 'is_active']);
        });

        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->foreignId('organisational_unit_id')
                ->nullable()
                ->after('department_id')
                ->constrained('organisational_units')
                ->nullOnDelete();
        });

        Schema::create('reporting_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subordinate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organisational_unit_id')->nullable()->constrained('organisational_units')->nullOnDelete();
            $table->string('relationship_type', 32)->default('line_manager');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['subordinate_id', 'effective_from'], 'reporting_subordinate_from_idx');
            $table->index(['supervisor_id', 'effective_from'], 'reporting_supervisor_from_idx');
        });

        Schema::create('delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('delegate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organisational_unit_id')->nullable()->constrained('organisational_units')->nullOnDelete();
            $table->string('scope', 80);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason', 500)->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['delegate_id', 'starts_at', 'ends_at'], 'delegations_delegate_window_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegations');
        Schema::dropIfExists('reporting_relationships');

        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organisational_unit_id');
        });

        Schema::dropIfExists('organisational_units');
    }
};
