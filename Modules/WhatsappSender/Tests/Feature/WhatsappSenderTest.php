<?php

namespace Modules\WhatsappSender\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
