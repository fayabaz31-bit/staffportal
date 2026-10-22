<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Duties express the specific assignment an officer performs (a "desk"),
     * independently of their broad role. A user may hold several duties and a
     * duty may be held by several users (PRD §2.1; Master Prompt §E: authorise
     * by action/scope, not by role name alone).
     */
    public function up(): void
    {
        Schema::create('duties', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('label');
            $table->string('unit', 32)->index();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('duty_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('duty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'duty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_user');
        Schema::dropIfExists('duties');
    }
};
