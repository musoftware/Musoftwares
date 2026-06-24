<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_workflow_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('module_type')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_workflow_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_definition_id')->constrained('erp_workflow_definitions')->cascadeOnDelete();
            $table->string('name');
            $table->integer('order')->default(1);
            $table->string('approver_type'); // 'user', 'role', 'manager'
            $table->unsignedBigInteger('approver_id')->nullable(); // nullable if 'manager'
            $table->boolean('requires_all')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_approval_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignUuid('workflow_definition_id')->constrained('erp_workflow_definitions')->cascadeOnDelete();
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->integer('current_step_order')->default(1);
            $table->unsignedBigInteger('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['approvable_type', 'approvable_id']);
        });

        Schema::create('erp_approval_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('approval_request_id')->constrained('erp_approval_requests')->cascadeOnDelete();
            $table->foreignUuid('workflow_step_id')->constrained('erp_workflow_steps')->cascadeOnDelete();
            $table->unsignedBigInteger('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // approved, rejected
            $table->text('comments')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_escalations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('approval_request_id')->constrained('erp_approval_requests')->cascadeOnDelete();
            $table->foreignUuid('workflow_step_id')->constrained('erp_workflow_steps')->cascadeOnDelete();
            $table->unsignedBigInteger('escalated_to_id')->nullable(); // could be user id or role id based on type
            $table->string('escalated_to_type')->default('user'); // user, role
            $table->timestamp('escalated_at')->useCurrent();
            $table->text('reason')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_escalations');
        Schema::dropIfExists('erp_approval_actions');
        Schema::dropIfExists('erp_approval_requests');
        Schema::dropIfExists('erp_workflow_steps');
        Schema::dropIfExists('erp_workflow_definitions');
    }
};
