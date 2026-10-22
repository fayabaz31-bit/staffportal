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
        Schema::create('memos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->longText('body');
            $table->string('classification', 20)->default('normal');
            $table->string('status', 20)->default('draft');
            $table->string('audience_type', 20);
            $table->foreignId('audience_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->json('audience_user_ids')->nullable();
            $table->boolean('requires_acknowledgement')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('withdrawal_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
            $table->index(['author_id', 'status', 'created_at']);
        });

        Schema::create('memo_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            $table->unique(['memo_id', 'user_id']);
            $table->index(['user_id', 'delivered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memo_recipients');
        Schema::dropIfExists('memos');
    }
};
