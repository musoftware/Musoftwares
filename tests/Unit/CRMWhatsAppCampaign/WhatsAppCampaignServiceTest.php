<?php

namespace Tests\Unit\CRMWhatsAppCampaign;

use Tests\TestCase;
use App\Modules\CRMWhatsAppCampaigns\Services\WhatsAppCampaignService;
use App\Modules\CRMWhatsAppCampaigns\Services\CRMWhatsAppCampaignLimitsService;
use App\Modules\CRMWhatsAppCampaigns\Services\CampaignAudienceResolver;
use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignCreated;
use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignStarted;
use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignCompleted;
use Modules\CRM\Models\WhatsAppCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class WhatsAppCampaignServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WhatsAppCampaignService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WhatsAppCampaignService::class);
        Event::fake();
        Queue::fake();
    }

    public function test_can_create_campaign(): void
    {
        $campaign = $this->service->create(1, [
            'name' => 'Test Campaign', 'type' => 'broadcast',
            'message_body' => 'Hello!', 'account_id' => 1,
        ]);

        $this->assertInstanceOf(WhatsAppCampaign::class, $campaign);
        $this->assertEquals('draft', $campaign->status);
        $this->assertEquals('broadcast', $campaign->type);
        Event::assertDispatched(WhatsAppCampaignCreated::class);
    }

    public function test_draft_campaign_can_start(): void
    {
        $campaign = WhatsAppCampaign::factory()->create([
            'workspace_id' => 1, 'audience_id' => 1, 'account_id' => 1,
            'message_body' => 'Hello', 'status' => 'draft',
        ]);

        $this->assertTrue($campaign->canStart());
        $this->assertFalse($campaign->canPause());
    }

    public function test_running_campaign_can_pause(): void
    {
        $campaign = WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);
        $result = $this->service->pause($campaign);

        $this->assertEquals('paused', $result->status);
        $this->assertNotNull($result->paused_at);
    }

    public function test_paused_campaign_can_resume(): void
    {
        $campaign = WhatsAppCampaign::factory()->create(['workspace_id' => 1, 'status' => 'paused', 'paused_at' => now()]);
        $result = $this->service->resume($campaign);

        $this->assertEquals('running', $result->status);
        $this->assertNull($result->paused_at);
    }

    public function test_campaign_can_be_cancelled(): void
    {
        $campaign = WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);
        $result = $this->service->cancel($campaign);

        $this->assertEquals('cancelled', $result->status);
        $this->assertNotNull($result->cancelled_at);
    }

    public function test_completed_campaign_cannot_start(): void
    {
        $campaign = WhatsAppCampaign::factory()->completed()->create(['workspace_id' => 1]);

        $this->expectException(\RuntimeException::class);
        $this->service->start($campaign);
    }

    public function test_campaign_can_be_duplicated(): void
    {
        $campaign = WhatsAppCampaign::factory()->completed()->create([
            'workspace_id' => 1, 'name' => 'Original Campaign',
        ]);

        $copy = $this->service->duplicate($campaign);

        $this->assertEquals('Original Campaign (Copy)', $copy->name);
        $this->assertEquals('draft', $copy->status);
        $this->assertEquals(0, $copy->sent_count);
    }

    public function test_campaign_calculates_delivery_rate(): void
    {
        $campaign = new WhatsAppCampaign(['sent_count' => 100, 'delivered_count' => 85]);
        $this->assertEquals(85.0, $campaign->getDeliveryRate());
    }

    public function test_campaign_calculates_progress(): void
    {
        $campaign = new WhatsAppCampaign([
            'total_recipients' => 200, 'sent_count' => 150, 'failed_count' => 10,
        ]);
        $this->assertEquals(80.0, $campaign->getProgressPercentage());
    }

    public function test_dashboard_returns_summary(): void
    {
        WhatsAppCampaign::factory()->count(3)->create(['workspace_id' => 1, 'status' => 'completed']);
        WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);

        $dashboard = $this->service->getDashboard(1);

        $this->assertEquals(4, $dashboard['total']);
        $this->assertEquals(1, $dashboard['running']);
        $this->assertEquals(3, $dashboard['completed']);
    }
}
