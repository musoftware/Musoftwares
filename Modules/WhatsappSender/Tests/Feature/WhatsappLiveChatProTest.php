<?php

namespace Modules\WhatsappSender\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Modules\WhatsappSender\Models\WhatsappContact;
use Modules\WhatsappSender\Models\WhatsappContactGroup;
use Tests\TestCase;

class WhatsappLiveChatProTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_send_media_chat_message(): void
    {
        $user = User::factory()->create();
        $business = WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Live Chat Business',
            'wallet_balance' => 50.00,
            'currency' => 'USD',
            'per_message_fee' => 0.0010,
            'bot_reply_fee' => 0.0005,
            'status' => 'active',
        ]);

        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'name' => 'Support Line',
            'phone_number_id' => '123456789012345',
            'access_token' => 'EAAG_META_TEST_SANDBOX_TOKEN_DEMO',
            'status' => 'active',
        ]);

        $fakePhoto = UploadedFile::fake()->image('offer.jpg', 800, 600);

        $response = $this->actingAs($user)->post("/whatsapp-sender/businesses/{$business->id}/send-chat-message", [
            'whatsapp_account_id' => $account->id,
            'recipient_phone' => '+201001234567',
            'message_type' => 'image',
            'media_file' => $fakePhoto,
            'caption' => 'Here is our latest special offer!',
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'log' => [
                    'recipient_phone' => '201001234567',
                    'message_type' => 'image',
                    'direction' => 'outbound',
                ],
            ]);
    }

    public function test_user_can_send_interactive_buttons_message(): void
    {
        $user = User::factory()->create();
        $business = WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Live Chat Business',
            'wallet_balance' => 50.00,
            'currency' => 'USD',
            'per_message_fee' => 0.0010,
            'bot_reply_fee' => 0.0005,
            'status' => 'active',
        ]);

        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'name' => 'Interactive Line',
            'phone_number_id' => '123456789012345',
            'access_token' => 'EAAG_META_TEST_SANDBOX_TOKEN_DEMO',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson("/whatsapp-sender/businesses/{$business->id}/send-chat-message", [
            'whatsapp_account_id' => $account->id,
            'recipient_phone' => '+201001234567',
            'message_type' => 'interactive',
            'message_body' => 'Would you like to book a demo call?',
            'buttons' => ['Book Demo', 'Pricing Info', 'Later'],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'log' => [
                    'message_type' => 'interactive',
                ],
            ]);
    }

    public function test_user_can_manage_canned_quick_replies(): void
    {
        $user = User::factory()->create();
        $business = WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Live Chat Business',
            'wallet_balance' => 50.00,
            'currency' => 'USD',
            'per_message_fee' => 0.0010,
            'bot_reply_fee' => 0.0005,
            'status' => 'active',
        ]);

        // 1. Get default quick replies
        $getRes = $this->actingAs($user)->getJson("/whatsapp-sender/businesses/{$business->id}/quick-replies");
        $getRes->assertOk()->assertJsonStructure(['success', 'quick_replies']);

        // 2. Store custom quick reply
        $storeRes = $this->actingAs($user)->postJson("/whatsapp-sender/businesses/{$business->id}/quick-replies", [
            'shortcut' => '/demo',
            'title' => 'Product Demo Link',
            'message' => 'You can try our demo at https://musoftwares.com/demo',
        ]);
        $storeRes->assertOk()->assertJson(['success' => true]);

        // 3. Delete quick reply
        $delRes = $this->actingAs($user)->deleteJson("/whatsapp-sender/businesses/{$business->id}/quick-replies/" . urlencode('/demo'));
        $delRes->assertOk()->assertJson(['success' => true]);
    }

    public function test_user_can_update_contact_crm_tags_and_notes(): void
    {
        $user = User::factory()->create();
        $business = WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Live Chat Business',
            'wallet_balance' => 50.00,
            'currency' => 'USD',
            'per_message_fee' => 0.0010,
            'bot_reply_fee' => 0.0005,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson("/whatsapp-sender/businesses/{$business->id}/contacts/crm", [
            'phone' => '+201009876543',
            'name' => 'Ahmed Mahmoud',
            'tags' => ['VIP', 'Lead'],
            'internal_notes' => 'Requested high-volume WhatsApp campaign pricing and custom API flow.',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'contact' => [
                    'name' => 'Ahmed Mahmoud',
                    'phone' => '201009876543',
                    'custom_fields' => [
                        'tags' => ['VIP', 'Lead'],
                        'internal_notes' => 'Requested high-volume WhatsApp campaign pricing and custom API flow.',
                    ],
                ],
            ]);
    }
}
