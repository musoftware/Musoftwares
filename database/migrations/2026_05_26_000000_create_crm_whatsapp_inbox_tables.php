<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. WhatsApp Accounts ─────────────────────────────────────
        Schema::create('crm_whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone_number');
            $table->string('provider')->default('baileys'); // baileys, cloud_api, waha
            $table->json('provider_config')->nullable();
            $table->text('session_data')->nullable(); // Encrypted
            $table->string('status')->default('disconnected'); // disconnected, connecting, connected, banned
            $table->text('qr_code')->nullable();
            $table->timestamp('qr_expires_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('health_status')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'phone_number']);
        });

        // ── 2. SLA Policies ──────────────────────────────────────────
        Schema::create('crm_whatsapp_sla_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('first_response_time'); // Minutes
            $table->unsignedInteger('resolution_time');     // Minutes
            $table->string('priority')->default('medium');  // low, medium, high, urgent
            $table->boolean('business_hours_only')->default(true);
            $table->boolean('notify_on_breach')->default(true);
            $table->foreignId('escalation_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workspace_id', 'is_active']);
        });

        // ── 3. Conversations ─────────────────────────────────────────
        Schema::create('crm_whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('crm_whatsapp_accounts')->cascadeOnDelete();
            $table->string('contact_phone');
            $table->string('contact_name')->nullable();
            $table->string('contact_avatar')->nullable();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('type')->default('general');     // lead, support, sales, general
            $table->string('status')->default('open');      // open, pending, resolved, archived
            $table->string('priority')->default('medium');  // low, medium, high, urgent
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assigned_department')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_preview', 500)->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('sla_policy_id')->nullable()->constrained('crm_whatsapp_sla_policies')->nullOnDelete();
            $table->timestamp('sla_due_at')->nullable();
            $table->boolean('sla_breached')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'assigned_agent_id']);
            $table->index(['workspace_id', 'contact_phone']);
            $table->index(['workspace_id', 'lead_id']);
            $table->index(['workspace_id', 'last_message_at']);
            $table->index(['workspace_id', 'sla_breached']);
            $table->index(['workspace_id', 'type']);
        });

        // ── 4. Messages ──────────────────────────────────────────────
        Schema::create('crm_whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('crm_whatsapp_conversations')->cascadeOnDelete();
            $table->string('sender_type');  // agent, customer, system, automation
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('whatsapp_message_id')->nullable();
            $table->string('type')->default('text'); // text, image, video, audio, document, sticker, reaction, template, location, contact
            $table->text('body')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_mime_type')->nullable();
            $table->unsignedInteger('media_size')->nullable();
            $table->string('media_filename')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->foreignId('quoted_message_id')->nullable()->constrained('crm_whatsapp_messages')->nullOnDelete();
            $table->string('reaction_emoji')->nullable();
            $table->string('template_name')->nullable();
            $table->json('template_params')->nullable();
            $table->string('delivery_status')->default('pending'); // pending, sent, delivered, read, failed
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

            $table->index(['conversation_id', 'created_at']);
            $table->index(['workspace_id', 'delivery_status']);
            $table->index('whatsapp_message_id');
        });

        // Add fulltext index for message search
        DB::statement('ALTER TABLE crm_whatsapp_messages ADD FULLTEXT INDEX crm_wa_messages_body_fulltext (body)');

        // ── 5. Participants ──────────────────────────────────────────
        Schema::create('crm_whatsapp_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('crm_whatsapp_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role')->default('participant'); // owner, participant, observer
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
        });

        // ── 6. Assignments (Audit Trail) ─────────────────────────────
        Schema::create('crm_whatsapp_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('crm_whatsapp_conversations')->cascadeOnDelete();
            $table->foreignId('assigned_from_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assignment_type')->default('manual'); // manual, auto, round_robin, department, workload, vip
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'conversation_id']);
        });

        // ── 7. Labels ────────────────────────────────────────────────
        Schema::create('crm_whatsapp_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 7)->default('#6366f1');
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['workspace_id', 'name']);
        });

        // ── 7b. Conversation-Label Pivot ──────────────────────────────
        Schema::create('crm_whatsapp_conversation_labels', function (Blueprint $table) {
            $table->foreignId('conversation_id')->constrained('crm_whatsapp_conversations')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('crm_whatsapp_labels')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['conversation_id', 'label_id']);
        });

        // ── 8. Automation Rules ──────────────────────────────────────
        Schema::create('crm_whatsapp_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // auto_reply, away_message, business_hours, routing, assignment, tag, lead_qualification, follow_up
            $table->string('trigger_event'); // message.received, conversation.created, conversation.assigned, etc.
            $table->json('conditions')->nullable();
            $table->json('actions');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->json('schedule')->nullable(); // Business hours config
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedInteger('trigger_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'is_active']);
            $table->index(['workspace_id', 'trigger_event']);
        });

        // ── 9. Message Logs (Audit) ──────────────────────────────────
        Schema::create('crm_whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('crm_whatsapp_messages')->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('crm_whatsapp_conversations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // sent, received, delivered, read, failed, retried, deleted
            $table->string('status')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'action', 'created_at']);
        });

        // ── 10. Quick Replies ────────────────────────────────────────
        Schema::create('crm_whatsapp_quick_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->string('shortcut'); // e.g., /greeting
            $table->string('title');
            $table->text('body');
            $table->string('media_url')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_global')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['workspace_id', 'shortcut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_whatsapp_quick_replies');
        Schema::dropIfExists('crm_whatsapp_message_logs');
        Schema::dropIfExists('crm_whatsapp_automation_rules');
        Schema::dropIfExists('crm_whatsapp_conversation_labels');
        Schema::dropIfExists('crm_whatsapp_labels');
        Schema::dropIfExists('crm_whatsapp_assignments');
        Schema::dropIfExists('crm_whatsapp_participants');
        Schema::dropIfExists('crm_whatsapp_messages');
        Schema::dropIfExists('crm_whatsapp_conversations');
        Schema::dropIfExists('crm_whatsapp_sla_policies');
        Schema::dropIfExists('crm_whatsapp_accounts');
    }
};
