<?php

namespace Tests\Unit\CRMWhatsAppInbox;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Contracts\WhatsAppProviderInterface;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppAccountConnected;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\WhatsAppSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\CRM\Models\WhatsAppAccount;
use Tests\TestCase;

class WhatsAppSessionManagerTest extends TestCase
{
    use RefreshDatabase;

    protected WhatsAppSessionManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(WhatsAppSessionManager::class);
    }

    public function test_initiate_connection_sets_status_to_connecting(): void
    {
        $account = WhatsAppAccount::factory()->disconnected()->create();

        $result = $this->manager->initiateConnection($account);

        $account->refresh();
        $this->assertEquals('connecting', $account->status);
        $this->assertArrayHasKey('status', $result);
    }

    public function test_handle_connection_success_updates_account(): void
    {
        Event::fake();

        $account = WhatsAppAccount::factory()->create(['status' => 'connecting']);

        $this->manager->handleConnectionSuccess($account, ['session_key' => 'test_data']);

        $account->refresh();
        $this->assertEquals('connected', $account->status);
        $this->assertNull($account->qr_code);
        $this->assertNotNull($account->last_seen_at);

        Event::assertDispatched(WhatsAppAccountConnected::class);
    }

    public function test_disconnect_clears_session(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create(['session_data' => 'encrypted_data']);

        $this->manager->disconnect($account);

        $account->refresh();
        $this->assertEquals('disconnected', $account->status);
        $this->assertNull($account->session_data);
    }

    public function test_check_health_returns_status(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();

        $health = $this->manager->checkHealth($account);

        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('device_info', $health);
        $this->assertArrayHasKey('last_seen', $health);
    }
}
