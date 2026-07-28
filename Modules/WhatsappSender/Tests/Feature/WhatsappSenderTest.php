<?php

namespace Modules\WhatsappSender\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Models\WhatsappLog;
use Tests\TestCase;

class WhatsappSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_dashboard_requires_auth()
    {
        $response = $this->get('/whatsapp-sender');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_whatsapp_dashboard()
    {
        $user = User::factory()->create();
        $user->subscriptions()->create([
            'object' => 'whatsapp-sender',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($user)->get('/whatsapp-sender');
        $response->assertStatus(200);
    }

    public function test_user_can_store_whatsapp_account_credentials()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/whatsapp-sender/accounts', [
            'name' => 'Test Business WhatsApp',
            'phone_number_id' => '102938475610293',
            'waba_id' => '9876543210',
            'access_token' => 'EAATestAccessTokenString',
        ]);

        $response->assertRedirect('/whatsapp-sender');

        $this->assertDatabaseHas('whatsapp_accounts', [
            'user_id' => $user->id,
            'name' => 'Test Business WhatsApp',
            'phone_number_id' => '102938475610293',
        ]);
    }

    public function test_user_can_delete_whatsapp_account()
    {
        $user = User::factory()->create();
        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'name' => 'To Delete Account',
            'phone_number_id' => '999888777',
            'access_token' => 'dummy',
        ]);

        $response = $this->actingAs($user)->delete('/whatsapp-sender/accounts/' . $account->id);

        $response->assertRedirect('/whatsapp-sender');
        $this->assertDatabaseMissing('whatsapp_accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_api_requires_sanctum_authentication()
    {
        $response = $this->postJson('/api/v1/whatsapp/send', [
            'recipient_phone' => '201001234567',
            'message_body' => 'Hello',
        ]);

        $response->assertStatus(401);
    }

    public function test_api_send_validates_e164_recipient_phone()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/whatsapp/send', [
            'recipient_phone' => 'invalid-phone-abc',
            'message_body' => 'Hello World',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'error',
            ]);
    }

    public function test_api_send_fails_gracefully_when_no_active_account()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/whatsapp/send', [
            'recipient_phone' => '201001234567',
            'message_body' => 'Test message',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'error',
            ]);
    }

    public function test_api_accounts_returns_connected_user_accounts()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        WhatsappAccount::create([
            'user_id' => $user->id,
            'name' => 'API Account 1',
            'phone_number_id' => '111222333',
            'access_token' => 'token123',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/whatsapp/accounts');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 1,
            ]);
    }

    public function test_user_can_register_whatsapp_account_with_pin()
    {
        \Illuminate\Support\Facades\Http::fake([
            'graph.facebook.com/*/register' => \Illuminate\Support\Facades\Http::response(['success' => true], 200),
        ]);

        $user = User::factory()->create();
        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'name' => 'To Register Account',
            'phone_number_id' => '123456789',
            'access_token' => 'EAATestToken',
            'status' => 'unregistered',
        ]);

        $response = $this->actingAs($user)->post("/whatsapp-sender/accounts/{$account->id}/register", [
            'pin' => '123456',
        ]);

        $response->assertRedirect('/whatsapp-sender');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $account->id,
            'status' => 'active',
        ]);
    }

    public function test_user_can_sync_whatsapp_account_status()
    {
        \Illuminate\Support\Facades\Http::fake([
            'graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response([
                'id' => '123456789',
                'verified_name' => 'Test Phone',
                'display_phone_number' => '+123456789',
                'quality_rating' => 'GREEN',
                'status' => 'CONNECTED',
            ], 200),
        ]);

        $user = User::factory()->create();
        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'name' => 'To Sync Account',
            'phone_number_id' => '123456789',
            'access_token' => 'EAATestToken',
            'status' => 'unregistered',
        ]);

        $response = $this->actingAs($user)->post("/whatsapp-sender/accounts/{$account->id}/sync");

        $response->assertRedirect('/whatsapp-sender');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $account->id,
            'status' => 'active',
        ]);

        $updatedAccount = WhatsappAccount::find($account->id);
        $this->assertEquals('CONNECTED', $updatedAccount->metadata['status']);
    }
}
