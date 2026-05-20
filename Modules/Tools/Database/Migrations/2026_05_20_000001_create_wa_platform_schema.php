<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. WhatsApp Accounts (registered numbers) ───────────────────────
        Schema::create('wa_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label');                          // friendly name
            $table->string('phone_number')->nullable();       // +966xxx (set after QR scan)
            $table->string('session_id')->unique();           // matches runtime accountId
            $table->string('status')->default('disconnected');// connected|disconnected|warming|banned
            $table->integer('health_score')->default(50);     // 0-100
            $table->string('trust_grade')->default('C');      // A+ to F
            $table->integer('warmup_day')->default(0);        // 0-14
            $table->integer('daily_limit')->default(50);      // recommended daily max
            $table->string('proxy')->nullable();
            $table->json('pool_numbers')->nullable();         // warmup partner numbers
            $table->json('capabilities')->nullable();         // what this account can do
            $table->timestamp('warmup_started_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('banned_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // ── 2. WhatsApp Campaigns ────────────────────────────────────────────
        Schema::create('wa_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('draft');      // draft|running|paused|completed|failed
            $table->string('runtime_campaign_id')->nullable();// matches inboxStore campaignId
            $table->json('account_ids');                      // session IDs to use
            $table->text('message_template');
            $table->string('media_url')->nullable();
            $table->string('humanize_preset')->default('moderate');
            $table->float('max_block_rate')->default(0.05);
            $table->integer('total_contacts')->default(0);
            $table->integer('sent')->default(0);
            $table->integer('failed')->default(0);
            $table->integer('blocked')->default(0);
            $table->integer('skipped')->default(0);
            $table->float('block_rate')->default(0);
            $table->integer('health_score_after')->nullable();
            $table->string('runtime_task_id')->nullable();   // agent taskId
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // ── 3. WhatsApp Contacts ─────────────────────────────────────────────
        Schema::create('wa_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phone');
            $table->string('name')->nullable();
            $table->string('company')->nullable();
            $table->string('city')->nullable();
            $table->string('timezone')->nullable();
            $table->string('language')->default('en');
            $table->string('lead_stage')->default('new');    // new|interested|qualified|converted|lost
            $table->integer('engagement_score')->default(0);
            $table->integer('reply_count')->default(0);
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamp('last_replied_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'phone']);
            $table->index(['user_id', 'lead_stage']);
            $table->index(['user_id', 'engagement_score']);
        });

        // ── 4. WhatsApp Contact-Campaign pivot (which contacts in which campaign) ─
        Schema::create('wa_campaign_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('wa_campaigns')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('wa_contacts')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending|sent|failed|skipped|blocked
            $table->string('account_id')->nullable();     // which session was used
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'contact_id']);
            $table->index(['campaign_id', 'status']);
        });

        // ── 5. WhatsApp Conversations ────────────────────────────────────────
        Schema::create('wa_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('wa_contacts')->nullOnDelete();
            $table->string('phone');
            $table->string('account_id');
            $table->string('status')->default('open');   // open|resolved|spam
            $table->string('label')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('unread_count')->default(0);
            $table->text('last_message')->nullable();
            $table->string('last_direction')->default('outbound');
            $table->boolean('ai_enabled')->default(false);
            $table->text('ai_summary')->nullable();
            $table->timestamp('last_msg_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'phone', 'account_id']);
            $table->index(['user_id', 'status', 'last_msg_at']);
            $table->index(['assigned_to']);
        });

        // ── 6. WhatsApp Messages ─────────────────────────────────────────────
        Schema::create('wa_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('wa_conversations')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('wa_campaigns')->nullOnDelete();
            $table->string('phone');
            $table->string('account_id');
            $table->string('direction');                    // outbound|inbound
            $table->text('content');
            $table->string('media_url')->nullable();
            $table->string('status')->default('sent');      // queued|sent|delivered|read|failed|blocked
            $table->boolean('ai_generated')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'phone', 'created_at']);
            $table->index(['campaign_id', 'status']);
            $table->index(['conversation_id']);
        });

        // ── 7. WhatsApp Workflows ────────────────────────────────────────────
        Schema::create('wa_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('draft');     // draft|active|paused
            $table->string('trigger_type')->default('manual'); // manual|keyword|new_contact|campaign_end
            $table->json('trigger_config')->nullable();
            $table->json('nodes');                          // ReactFlow nodes
            $table->json('edges');                          // ReactFlow edges
            $table->integer('active_contacts')->default(0);
            $table->integer('completed_contacts')->default(0);
            $table->timestamps();
        });

        // ── 8. WhatsApp Workflow States ──────────────────────────────────────
        Schema::create('wa_workflow_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('wa_workflows')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('wa_contacts')->cascadeOnDelete();
            $table->string('current_node_id');
            $table->string('status')->default('waiting');   // waiting|processing|completed|failed
            $table->json('variables')->nullable();           // contextual variables
            $table->timestamp('next_execution_at')->nullable();
            $table->timestamps();

            $table->unique(['workflow_id', 'contact_id']);
            $table->index(['status', 'next_execution_at']);
        });

        // ── 9. WhatsApp Quality Events ───────────────────────────────────────
        Schema::create('wa_quality_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('account_id');
            $table->string('event_type');                   // sent|blocked|replied|circuit_opened|ban_detected
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->index(['account_id', 'event_type', 'occurred_at']);
        });

        // ── 10. WhatsApp Contact Lists (segmentation) ────────────────────────
        Schema::create('wa_contact_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('contact_count')->default(0);
            $table->json('filters')->nullable();            // saved filter criteria
            $table->timestamps();
        });

        Schema::create('wa_contact_list_members', function (Blueprint $table) {
            $table->foreignId('list_id')->constrained('wa_contact_lists')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('wa_contacts')->cascadeOnDelete();
            $table->primary(['list_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_contact_list_members');
        Schema::dropIfExists('wa_contact_lists');
        Schema::dropIfExists('wa_quality_events');
        Schema::dropIfExists('wa_workflow_states');
        Schema::dropIfExists('wa_workflows');
        Schema::dropIfExists('wa_messages');
        Schema::dropIfExists('wa_conversations');
        Schema::dropIfExists('wa_campaign_contacts');
        Schema::dropIfExists('wa_contacts');
        Schema::dropIfExists('wa_campaigns');
        Schema::dropIfExists('wa_accounts');
    }
};
