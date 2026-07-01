<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Project audit log: append-only history of admin mutations against a project.
     * One row per logical action (created/updated/archived/restored/deleted/bulk_*).
     */
    public function up(): void
    {
        Schema::create('project_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);          // created, updated, archived, restored, deleted, bulk_archived, bulk_restored, bulk_deleted
            $table->json('changes')->nullable();   // before/after diff for updated
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['project_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_audit_logs');
    }
};
