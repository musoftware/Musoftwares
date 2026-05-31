<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop all CRM WhatsApp tables.
     * WhatsApp functionality has been removed from the CRM module.
     */
    public function up(): void
    {
        // Campaign-related tables (drop children first)
        Schema::dropIfExists('crm_wa_campaign_ab_variants');
        Schema::dropIfExists('crm_wa_campaign_audience_members');
        Schema::dropIfExists('crm_wa_campaign_audiences');
        Schema::dropIfExists('crm_wa_campaign_analytics');
        Schema::dropIfExists('crm_wa_campaign_deliveries');
        Schema::dropIfExists('crm_wa_campaign_events');
        Schema::dropIfExists('crm_wa_campaign_sequence_steps');
        Schema::dropIfExists('crm_wa_campaign_sequences');
        Schema::dropIfExists('crm_wa_campaign_templates');
        Schema::dropIfExists('crm_wa_campaigns');

        // Inbox-related tables (drop children first)
        Schema::dropIfExists('crm_wa_message_logs');
        Schema::dropIfExists('crm_wa_messages');
        Schema::dropIfExists('crm_wa_assignments');
        Schema::dropIfExists('crm_wa_participants');
        Schema::dropIfExists('crm_wa_conversations');
        Schema::dropIfExists('crm_wa_automation_rules');
        Schema::dropIfExists('crm_wa_labels');
        Schema::dropIfExists('crm_wa_quick_replies');
        Schema::dropIfExists('crm_wa_sla_policies');

        // Core WhatsApp table (drop last)
        Schema::dropIfExists('crm_whatsapp_accounts');
    }

    /**
     * WhatsApp tables are permanently removed — no rollback.
     */
    public function down(): void
    {
        // No rollback — WhatsApp has been fully removed from CRM.
    }
};
