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
        // crm_whatsapp_accounts
        Schema::create('crm_whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->string('provider')->default('official'); // e.g., 'official', 'baileys'
            $table->text('provider_config')->nullable();
            $table->text('session_data')->nullable();
            $table->string('status')->default('disconnected');
            $table->text('qr_code')->nullable();
            $table->timestamp('qr_expires_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('health_status')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('workspace_id')->references('id')->on('crm_workspaces')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });

        // crm_whatsapp_sla_policies
        Schema::create('crm_whatsapp_sla_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->string('name');
            $table->integer('first_response_time')->default(15);
            $table->integer('resolution_time')->default(60);
            $table->string('priority')->default('normal');
            $table->boolean('business_hours_only')->default(false);
            $table->boolean('notify_on_breach')->default(true);
            $table->unsignedBigInteger('escalation_user_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('workspace_id')->references('id')->on('crm_workspaces')->onDelete('cascade');
            $table->foreign('escalation_user_id')->references('id')->on('users')->onDelete('set null');
        });

        // crm_whatsapp_conversations
        Schema::create('crm_whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('account_id');
            $table->string('contact_phone');
            $table->string('contact_name')->nullable();
            $table->string('contact_avatar')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('type')->default('individual');
            $table->string('status')->default('open');
            $table->string('priority')->default('normal');
            $table->unsignedBigInteger('assigned_agent_id')->nullable();
            $table->string('assigned_department')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->integer('unread_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->text('last_message_preview')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('sla_policy_id')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->boolean('sla_breached')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('workspace_id')->references('id')->on('crm_workspaces')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('crm_whatsapp_accounts')->onDelete('cascade');
            $table->foreign('assigned_agent_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('sla_policy_id')->references('id')->on('crm_whatsapp_sla_policies')->onDelete('set null');
            // assuming crm_leads exists
            // $table->foreign('lead_id')->references('id')->on('crm_leads')->onDelete('set null');
        });

        // crm_whatsapp_messages
        Schema::create('crm_whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('conversation_id');
            $table->string('sender_type'); // 'agent' or 'customer'
            $table->unsignedBigInteger('sender_id')->nullable(); // user_id if agent
            $table->string('whatsapp_message_id')->nullable(); // External ID
            $table->string('type')->default('text');
            $table->text('body')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_mime_type')->nullable();
            $table->integer('media_size')->nullable();
            $table->string('media_filename')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->unsignedBigInteger('quoted_message_id')->nullable();
            $table->string('reaction_emoji')->nullable();
            $table->string('template_name')->nullable();
            $table->json('template_params')->nullable();
            $table->string('delivery_status')->default('pending');
            $table->text('failed_reason')->nullable();
            $table->boolean('is_internal_note')->default(false);
            $table->json('mentions')->nullable();
            $table->boolean('is_starred')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('workspace_id')->references('id')->on('crm_workspaces')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('crm_whatsapp_conversations')->onDelete('cascade');
            $table->foreign('quoted_message_id')->references('id')->on('crm_whatsapp_messages')->onDelete('set null');
        });

        // crm_whatsapp_labels
        Schema::create('crm_whatsapp_labels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->string('name');
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('workspace_id')->references('id')->on('crm_workspaces')->onDelete('cascade');
        });

        // crm_whatsapp_conversation_labels
        Schema::create('crm_whatsapp_conversation_labels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('label_id');
            $table->timestamps();
            
            $table->foreign('conversation_id')->references('id')->on('crm_whatsapp_conversations')->onDelete('cascade');
            $table->foreign('label_id')->references('id')->on('crm_whatsapp_labels')->onDelete('cascade');
            $table->unique(['conversation_id', 'label_id'], 'conv_label_unique');
        });

        // crm_whatsapp_message_logs
        Schema::create('crm_whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('message_id')->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('status')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('workspace_id')->references('id')->on('crm_workspaces')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('crm_whatsapp_messages')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('crm_whatsapp_conversations')->onDelete('cascade');
        });

        // crm_whatsapp_participants
        Schema::create('crm_whatsapp_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
            
            $table->foreign('workspace_id')->references('id')->on('crm_workspaces')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('crm_whatsapp_conversations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // crm_whatsapp_assignments
        Schema::create('crm_whatsapp_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('assigned_from_id')->nullable();
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->unsignedBigInteger('assigned_by_id')->nullable();
            $table->string('assignment_type')->default('manual');
            $table->text('reason')->nullable();
            $table->timestamps();
            
            $table->foreign('workspace_id')->references('id')->on('crm_workspaces')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('crm_whatsapp_conversations')->onDelete('cascade');
            $table->foreign('assigned_from_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('assigned_to_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('assigned_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_whatsapp_assignments');
        Schema::dropIfExists('crm_whatsapp_participants');
        Schema::dropIfExists('crm_whatsapp_message_logs');
        Schema::dropIfExists('crm_whatsapp_conversation_labels');
        Schema::dropIfExists('crm_whatsapp_labels');
        Schema::dropIfExists('crm_whatsapp_messages');
        Schema::dropIfExists('crm_whatsapp_conversations');
        Schema::dropIfExists('crm_whatsapp_sla_policies');
        Schema::dropIfExists('crm_whatsapp_accounts');
    }
};
