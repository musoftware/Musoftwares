<?php

namespace Tests\Unit\CRMWhatsAppCampaign;

use Tests\TestCase;
use App\Modules\CRMWhatsAppCampaigns\Services\CampaignDeliveryManager;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;

class CampaignDeliveryManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); Queue::fake(); Event::fake(); }

    public function test_delivery_can_retry(): void
    {
        $delivery = new WhatsAppCampaignDelivery(['status' => 'failed', 'retry_count' => 1, 'max_retries' => 2]);
        $this->assertTrue($delivery->canRetry());
    }

    public function test_delivery_cannot_retry_when_exhausted(): void
    {
        $delivery = new WhatsAppCampaignDelivery(['status' => 'failed', 'retry_count' => 2, 'max_retries' => 2]);
        $this->assertFalse($delivery->canRetry());
    }

    public function test_delivery_scopes(): void
    {
        WhatsAppCampaignDelivery::factory()->create(['workspace_id' => 1, 'campaign_id' => 1, 'status' => 'pending']);
        WhatsAppCampaignDelivery::factory()->sent()->create(['workspace_id' => 1, 'campaign_id' => 1]);
        WhatsAppCampaignDelivery::factory()->failed()->create(['workspace_id' => 1, 'campaign_id' => 1]);

        $this->assertEquals(1, WhatsAppCampaignDelivery::withoutGlobalScopes()->pending()->count());
        $this->assertEquals(1, WhatsAppCampaignDelivery::withoutGlobalScopes()->sent()->count());
        $this->assertEquals(1, WhatsAppCampaignDelivery::withoutGlobalScopes()->failed()->count());
    }

    public function test_mark_as_sent_updates_fields(): void
    {
        $delivery = WhatsAppCampaignDelivery::factory()->create(['workspace_id' => 1, 'campaign_id' => 1]);
        $delivery->markAsSent('msg_123');

        $this->assertEquals('sent', $delivery->fresh()->status);
        $this->assertEquals('msg_123', $delivery->fresh()->whatsapp_message_id);
        $this->assertNotNull($delivery->fresh()->sent_at);
    }

    public function test_mark_as_failed_updates_fields(): void
    {
        $delivery = WhatsAppCampaignDelivery::factory()->create(['workspace_id' => 1, 'campaign_id' => 1]);
        $delivery->markAsFailed('Connection timeout');

        $this->assertEquals('failed', $delivery->fresh()->status);
        $this->assertEquals('Connection timeout', $delivery->fresh()->failed_reason);
    }
}
