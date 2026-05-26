<?php

namespace Tests\Feature\CRMWhatsAppCampaign;

use Tests\TestCase;
use App\Models\User;
use App\Modules\CRMWhatsAppCampaigns\Services\CampaignDeliveryManager;
use App\Modules\CRMWhatsAppCampaigns\Services\CampaignAudienceResolver;
use App\Modules\CRMWhatsAppCampaigns\Services\WhatsAppTemplateRenderer;
use App\Modules\CRMWhatsAppInbox\Contracts\WhatsAppProviderInterface;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignAudience;
use Modules\CRM\Models\WhatsAppCampaignAudienceMember;
use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Modules\CRM\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;

class CampaignDeliveryFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Event::fake();
    }

    public function test_delivery_queue_generated_from_audience(): void
    {
        $audience = WhatsAppCampaignAudience::factory()->create(['workspace_id' => 1]);
        WhatsAppCampaignAudienceMember::create([
            'workspace_id' => 1, 'audience_id' => $audience->id,
            'phone' => '+201234567890', 'name' => 'Ahmed',
            'merge_data' => ['customer_name' => 'Ahmed'],
        ]);
        WhatsAppCampaignAudienceMember::create([
            'workspace_id' => 1, 'audience_id' => $audience->id,
            'phone' => '+201234567891', 'name' => 'Mona',
            'merge_data' => ['customer_name' => 'Mona'],
        ]);

        $campaign = WhatsAppCampaign::factory()->create([
            'workspace_id' => 1, 'audience_id' => $audience->id,
            'message_body' => 'Hello {{customer_name}}!', 'status' => 'running',
        ]);

        $manager = app(CampaignDeliveryManager::class);
        $count = $manager->generateDeliveryQueue($campaign);

        $this->assertEquals(2, $count);
        $this->assertEquals(2, $campaign->fresh()->total_recipients);
        $this->assertDatabaseHas('crm_wa_campaign_deliveries', ['phone' => '+201234567890', 'rendered_body' => 'Hello Ahmed!']);
        $this->assertDatabaseHas('crm_wa_campaign_deliveries', ['phone' => '+201234567891', 'rendered_body' => 'Hello Mona!']);
    }

    public function test_delivery_deduplicates_same_phone(): void
    {
        $audience = WhatsAppCampaignAudience::factory()->create(['workspace_id' => 1]);
        WhatsAppCampaignAudienceMember::create([
            'workspace_id' => 1, 'audience_id' => $audience->id, 'phone' => '+201234567890', 'name' => 'Ahmed',
        ]);
        WhatsAppCampaignAudienceMember::create([
            'workspace_id' => 1, 'audience_id' => $audience->id, 'phone' => '+201234567890', 'name' => 'Ahmed Duplicate',
        ]);

        $campaign = WhatsAppCampaign::factory()->create([
            'workspace_id' => 1, 'audience_id' => $audience->id,
            'message_body' => 'Hello!', 'status' => 'running',
        ]);

        $manager = app(CampaignDeliveryManager::class);
        $count = $manager->generateDeliveryQueue($campaign);

        $this->assertEquals(1, $count); // Deduped
    }

    public function test_delivery_renders_template_placeholders(): void
    {
        $renderer = new WhatsAppTemplateRenderer();
        $body = 'Hi {{customer_name}}, your order {{order_id}} from {{company_name}} is ready!';
        $data = ['customer_name' => 'Sara', 'order_id' => 'ORD-789', 'company_name' => 'TechStore'];

        $rendered = $renderer->render($body, $data);
        $this->assertEquals('Hi Sara, your order ORD-789 from TechStore is ready!', $rendered);
    }

    public function test_is_complete_when_no_pending_deliveries(): void
    {
        $campaign = WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);
        WhatsAppCampaignDelivery::factory()->sent()->count(5)->create([
            'workspace_id' => 1, 'campaign_id' => $campaign->id,
        ]);

        $manager = app(CampaignDeliveryManager::class);
        $this->assertTrue($manager->isComplete($campaign));
    }

    public function test_is_not_complete_with_pending_deliveries(): void
    {
        $campaign = WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);
        WhatsAppCampaignDelivery::factory()->count(3)->create([
            'workspace_id' => 1, 'campaign_id' => $campaign->id, 'status' => 'pending',
        ]);

        $manager = app(CampaignDeliveryManager::class);
        $this->assertFalse($manager->isComplete($campaign));
    }

    public function test_webhook_updates_delivery_status(): void
    {
        $campaign = WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);
        $delivery = WhatsAppCampaignDelivery::factory()->sent()->create([
            'workspace_id' => 1, 'campaign_id' => $campaign->id,
            'whatsapp_message_id' => 'msg_test_123',
        ]);

        $manager = app(CampaignDeliveryManager::class);
        $manager->updateDeliveryStatus('msg_test_123', 'delivered');

        $this->assertEquals('delivered', $delivery->fresh()->status);
        $this->assertNotNull($delivery->fresh()->delivered_at);
    }
}
