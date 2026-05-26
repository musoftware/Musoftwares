<?php

namespace Tests\Feature\CRMWhatsAppCampaign;

use Tests\TestCase;
use Modules\CRM\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class CampaignWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); Queue::fake(); }

    public function test_webhook_accepted_with_valid_account(): void
    {
        $account = WhatsAppAccount::factory()->create(['workspace_id' => 1, 'status' => 'connected']);

        $response = $this->postJson(route('crm.whatsapp-campaigns.webhook'), [
            'account_id' => $account->id,
            'message_id' => 'msg_test_webhook',
            'status'     => 'delivered',
        ]);

        $response->assertStatus(202);
        $response->assertJson(['status' => 'accepted']);
    }

    public function test_webhook_rejects_missing_account(): void
    {
        $response = $this->postJson(route('crm.whatsapp-campaigns.webhook'), [
            'message_id' => 'msg_test_no_account',
            'status'     => 'delivered',
        ]);

        $response->assertStatus(400);
    }

    public function test_webhook_rejects_invalid_account(): void
    {
        $response = $this->postJson(route('crm.whatsapp-campaigns.webhook'), [
            'account_id' => 999999,
            'message_id' => 'msg_test_invalid',
            'status'     => 'delivered',
        ]);

        $response->assertStatus(404);
    }

    public function test_webhook_dispatches_processing_job(): void
    {
        $account = WhatsAppAccount::factory()->create(['workspace_id' => 1, 'status' => 'connected']);

        $this->postJson(route('crm.whatsapp-campaigns.webhook'), [
            'account_id' => $account->id,
            'message_id' => 'msg_test_job',
            'status'     => 'read',
        ]);

        Queue::assertPushed(\App\Modules\CRMWhatsAppCampaigns\Jobs\ProcessCampaignWebhookJob::class);
    }
}
