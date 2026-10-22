<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registry chain-of-command routing for profile-change requests (OD-20).
     *
     * Routine Registry work rises officer → Head of HR → Registrar, and is
     * escalated to the Director only for matters the institution reserves to
     * that office.
     */
    public function up(): void
    {
        Schema::table('profile_change_requests', function (Blueprint $table) {
            $table->string('current_stage', 24)->default('head_of_hr')->after('status');
            $table->foreignId('head_reviewed_by')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
            $table->timestamp('head_reviewed_at')->nullable()->after('head_reviewed_by');
            $table->text('head_note')->nullable()->after('head_reviewed_at');
            $table->foreignId('registrar_approved_by')->nullable()->after('head_note')->constrained('users')->nullOnDelete();
            $table->timestamp('registrar_approved_at')->nullable()->after('registrar_approved_by');
            $table->foreignId('escalated_by')->nullable()->after('registrar_approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('escalated_at')->nullable()->after('escalated_by');
            $table->boolean('requires_director')->default(false)->after('escalated_at');

            $table->index(['status', 'current_stage']);
        });
    }

    public function down(): void
    {
        Schema::table('profile_change_requests', function (Blueprint $table) {
            $table->dropIndex(['status', 'current_stage']);
            $table->dropConstrainedForeignId('head_reviewed_by');
            $table->dropColumn(['head_reviewed_at', 'head_note']);
            $table->dropConstrainedForeignId('registrar_approved_by');
            $table->dropColumn(['registrar_approved_at', 'requires_director']);
            $table->dropConstrainedForeignId('escalated_by');
            $table->dropColumn(['escalated_at', 'current_stage']);
        });
    }
};
