<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP Task System — recovered from old project: tasks + todos tables.
 * Scoped per-tenant. Tasks link to TenantClients and Projects.
 * TodoItems carry the granular work items with priority, cost, scheduling.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── ERP Task Boards ──────────────────────────────────────────
        // Guard against partial-run failures
        if (!Schema::hasTable('erp_tasks')) {
            Schema::create('erp_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();

                $table->string('task_name');
                $table->text('task_description')->nullable();

                // Client and project linkage — recovered from old Task model
                $table->foreignId('client_id')->nullable()->constrained('erp_tenant_clients')->nullOnDelete();
                $table->foreignId('project_id')->nullable()->constrained('erp_projects')->nullOnDelete();

                // Status lifecycle: open | in_progress | completed | archived
                $table->string('status')->default('open');
                $table->boolean('archived')->default(false);

                // Priority: low | normal | high | urgent
                $table->string('priority')->default('normal');

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamp('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'client_id']);
                $table->index(['tenant_id', 'archived']);
            });
        }

        // ── ERP Todo Items ───────────────────────────────────────────
        if (!Schema::hasTable('erp_todo_items')) {
            Schema::create('erp_todo_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('task_id')->constrained('erp_tasks')->cascadeOnDelete();

                $table->string('title');
                $table->text('description')->nullable();

                $table->boolean('completed')->default(false);
                $table->timestamp('completed_at')->nullable();

                // Priority visual system — recovered from old project
                $table->string('priority')->default('normal');
                $table->string('priority_color')->default('#6366f1');

                // Drag-and-drop ordering — recovered from old project: sort_index
                $table->unsignedInteger('sort_index')->default(0);

                // Pause/resume workflow — recovered from old project
                $table->boolean('paused')->default(false);

                // Billable cost tracking — recovered from old project: cost, is_paid
                $table->decimal('cost', 10, 2)->nullable();
                $table->string('cost_currency', 3)->nullable();
                $table->boolean('is_paid')->default(false);

                // Time scheduling — recovered from old project: start_at, end_at
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();

                // JSON tags array — recovered from old project
                $table->json('tags')->nullable();

                // Nested todos (sub-items) — recovered from old project: parent_id
                $table->foreignId('parent_id')->nullable()->constrained('erp_todo_items')->nullOnDelete();

                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['task_id', 'completed', 'sort_index']);
            });
        }

        // ── Client Notes (ERP) ───────────────────────────────────────
        // Parallel to platform-level user_notes.
        // Tenant manages notes about their TenantClients.
        if (!Schema::hasTable('erp_client_notes')) {
            Schema::create('erp_client_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('client_id')->constrained('erp_tenant_clients')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                // Categories: password | anydesk | notes | archived
                $table->string('category')->default('notes');
                $table->string('original_category')->nullable();

                $table->string('title');
                $table->text('content');

                $table->timestamps();
                $table->index(['tenant_id', 'client_id', 'category']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_client_notes');
        Schema::dropIfExists('erp_todo_items');
        Schema::dropIfExists('erp_tasks');
    }
};
