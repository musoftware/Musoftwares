<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Campaign Templates ────────────────────────────────────
        Schema::create('crm_wa_campaign_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('type')->default('text'); // text, image, video, document, template, interactive
            $table->text('body')->nullable();
            $table->json('placeholders')->nullable(); // [{key: 'customer_name', fallback: 'Customer'}]
            $table->string('media_url')->nullable();
            $table->string('media_mime_type')->nullable();
            $table->string('media_filename')->nullable();
            $table->string('header_text')->nullable();
            $table->string('footer_text')->nullable();
            $table->json('buttons')->nullable();      // [{type: 'url', text: 'Visit', url: '...'}, {type: 'reply', text: 'Yes'}]
            $table->json('quick_replies')->nullable(); // ['Option A', 'Option B']
            $table->string('cta_url')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('wa_template_name')->nullable();  // For WhatsApp Business API approved templates
            $table->string('wa_template_language')->nullable();
            $table->json('wa_template_params')->nullable();
            $table->string('category')->nullable();    // marketing, utility, transactional
            $table->boolean('is_approved')->default(true);
            $table->unsignedInteger('usage_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'type']);
            $table->index(['workspace_id', 'category']);
        });

        // ── 2. Campaign Audiences ────────────────────────────────────
        Schema::create('crm_wa_campaign_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('filters')->nullable();         // [{field: 'status', operator: 'in', value: ['new','qualified']}, ...]
            $table->string('source_type')->default('leads'); // leads, customers, contacts, manual, csv
            $table->unsignedInteger('estimated_size')->default(0);
            $table->unsignedInteger('resolved_size')->default(0);
            $table->timestamp('last_resolved_at')->nullable();
            $table->json('suppression_rules')->nullable(); // Opt-out, recently contacted, etc.
            $table->boolean('is_dynamic')->default(true);  // Re-resolve on each campaign use
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'source_type']);
        });

        // ── 3. Audience Members (Resolved) ───────────────────────────
        Schema::create('crm_wa_campaign_audience_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->foreignId('audience_id')->constrained('crm_wa_campaign_audiences')->cascadeOnDelete();
            $table->string('phone');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->morphs('contactable');               // lead_id, customer_id, etc.
            $table->json('merge_data')->nullable();       // Personalization data for placeholders
            $table->boolean('is_opted_out')->default(false);
            $table->boolean('is_suppressed')->default(false);
            $table->string('suppression_reason')->nullable();
            $table->timestamps();

            $table->unique(['audience_id', 'phone']);
            $table->index(['workspace_id', 'audience_id']);
        });

        // ── 4. Campaigns ─────────────────────────────────────────────
        Schema::create('crm_wa_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('broadcast'); // broadcast, drip, nurture, reactivation, abandoned_cart, follow_up, promotional, transactional
            $table->string('status')->default('draft');   // draft, scheduled, running, paused, completed, failed, cancelled
            $table->foreignId('template_id')->nullable()->constrained('crm_wa_campaign_templates')->nullOnDelete();
            $table->foreignId('audience_id')->nullable()->constrained('crm_wa_campaign_audiences')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('crm_whatsapp_accounts')->nullOnDelete();
            $table->json('account_rotation')->nullable(); // [account_id_1, account_id_2, ...] for multi-account rotation

            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('paused_at')->nullable();

            // Throttling
            $table->unsignedInteger('batch_size')->default(50);
            $table->unsignedInteger('batch_delay_seconds')->default(5);
            $table->unsignedInteger('max_per_minute')->nullable();
            $table->unsignedInteger('max_per_hour')->nullable();

            // Stats (denormalized for performance)
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('read_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('replied_count')->default(0);
            $table->unsignedInteger('clicked_count')->default(0);
            $table->unsignedInteger('opted_out_count')->default(0);

            // Message content (inline, if no template)
            $table->text('message_body')->nullable();
            $table->string('message_type')->default('text');
            $table->string('media_url')->nullable();
            $table->json('buttons')->nullable();

            // Automation trigger
            $table->string('trigger_event')->nullable();   // lead.created, lead.stage_changed, etc.
            $table->json('trigger_conditions')->nullable();

            // Meta
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'type']);
            $table->index(['workspace_id', 'scheduled_at']);
            $table->index(['workspace_id', 'trigger_event']);
        });

        // ── 5. Campaign Sequences ────────────────────────────────────
        Schema::create('crm_wa_campaign_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('crm_wa_campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('total_steps')->default(0);
            $table->json('exit_conditions')->nullable();  // [{field: 'replied', operator: 'is_true'}]
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'campaign_id']);
        });

        // ── 6. Sequence Steps ────────────────────────────────────────
        Schema::create('crm_wa_campaign_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->foreignId('sequence_id')->constrained('crm_wa_campaign_sequences')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('action_type')->default('send_message'); // send_message, wait, condition, update_lead, add_tag, remove_tag, exit
            $table->foreignId('template_id')->nullable()->constrained('crm_wa_campaign_templates')->nullOnDelete();
            $table->text('message_body')->nullable();
            $table->string('message_type')->default('text');

            // Delay/Wait
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->string('delay_unit')->default('minutes'); // minutes, hours, days

            // Conditional branching
            $table->json('conditions')->nullable();           // [{field: 'delivery_status', operator: 'eq', value: 'read'}]
            $table->unsignedInteger('on_true_step')->nullable();  // Jump to step if condition true
            $table->unsignedInteger('on_false_step')->nullable(); // Jump to step if condition false

            // Stop/Skip logic
            $table->boolean('skip_if_replied')->default(false);
            $table->boolean('stop_on_reply')->default(false);

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['sequence_id', 'step_order']);
        });

        // ── 7. Campaign Deliveries ───────────────────────────────────
        Schema::create('crm_wa_campaign_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('crm_wa_campaigns')->cascadeOnDelete();
            $table->foreignId('sequence_step_id')->nullable()->constrained('crm_wa_campaign_sequence_steps')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('crm_whatsapp_accounts')->nullOnDelete();
            $table->string('phone');
            $table->string('contact_name')->nullable();
            $table->nullableMorphs('contactable');        // Lead, Customer, etc.
            $table->text('rendered_body')->nullable();     // Final rendered message with placeholders filled
            $table->string('message_type')->default('text');
            $table->string('media_url')->nullable();

            // Delivery tracking
            $table->string('status')->default('pending');  // pending, queued, sent, delivered, read, failed, skipped, opted_out
            $table->string('whatsapp_message_id')->nullable();
            $table->text('failed_reason')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->unsignedTinyInteger('max_retries')->default(2);

            // Engagement
            $table->boolean('has_replied')->default(false);
            $table->boolean('has_clicked')->default(false);
            $table->boolean('has_opted_out')->default(false);

            // Timestamps
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('clicked_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'phone', 'sequence_step_id'], 'wa_delivery_dedup');
            $table->index(['workspace_id', 'campaign_id', 'status']);
            $table->index(['workspace_id', 'status']);
            $table->index('whatsapp_message_id');
        });

        // ── 8. Campaign Events (Lifecycle Audit) ─────────────────────
        Schema::create('crm_wa_campaign_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('crm_wa_campaigns')->cascadeOnDelete();
            $table->string('event');               // created, started, paused, resumed, completed, failed, cancelled, audience_resolved, batch_sent
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();  // Snapshot of stats at event time
            $table->timestamps();

            $table->index(['campaign_id', 'event']);
            $table->index(['workspace_id', 'created_at']);
        });

        // ── 9. Campaign Analytics (Aggregated Snapshots) ─────────────
        Schema::create('crm_wa_campaign_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('crm_wa_campaigns')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('hour')->nullable();  // 0-23, null = daily aggregate
            $table->unsignedInteger('sent')->default(0);
            $table->unsignedInteger('delivered')->default(0);
            $table->unsignedInteger('read')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('replied')->default(0);
            $table->unsignedInteger('clicked')->default(0);
            $table->unsignedInteger('opted_out')->default(0);
            $table->decimal('delivery_rate', 5, 2)->default(0);
            $table->decimal('read_rate', 5, 2)->default(0);
            $table->decimal('reply_rate', 5, 2)->default(0);
            $table->decimal('click_rate', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'date', 'hour'], 'wa_analytics_unique');
            $table->index(['workspace_id', 'date']);
        });

        // ── 10. A/B Variants (Architecture-Ready) ────────────────────
        Schema::create('crm_wa_campaign_ab_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('crm_wa_campaigns')->cascadeOnDelete();
            $table->string('variant_name');           // A, B, C
            $table->foreignId('template_id')->nullable()->constrained('crm_wa_campaign_templates')->nullOnDelete();
            $table->text('message_body')->nullable();
            $table->unsignedInteger('audience_percentage')->default(50); // % of audience
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('read_count')->default(0);
            $table->unsignedInteger('replied_count')->default(0);
            $table->boolean('is_winner')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'variant_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_wa_campaign_ab_variants');
        Schema::dropIfExists('crm_wa_campaign_analytics');
        Schema::dropIfExists('crm_wa_campaign_events');
        Schema::dropIfExists('crm_wa_campaign_deliveries');
        Schema::dropIfExists('crm_wa_campaign_sequence_steps');
        Schema::dropIfExists('crm_wa_campaign_sequences');
        Schema::dropIfExists('crm_wa_campaigns');
        Schema::dropIfExists('crm_wa_campaign_audience_members');
        Schema::dropIfExists('crm_wa_campaign_audiences');
        Schema::dropIfExists('crm_wa_campaign_templates');
    }
};
