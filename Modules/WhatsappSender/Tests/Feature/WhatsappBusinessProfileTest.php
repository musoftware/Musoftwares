<?php

namespace Modules\WhatsappSender\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Tests\TestCase;

class WhatsappBusinessProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_get_whatsapp_business_profile_and_health(): void
    {
        $user = User::factory()->create();
        $business = WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Profile Test Business',
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
            'waba_id' => '987654321098765',
            'access_token' => 'EAAG_META_TEST_SANDBOX_TOKEN_DEMO',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->getJson("/whatsapp-sender/accounts/{$account->id}/profile");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_sandbox' => true,
            ])
            ->assertJsonStructure([
                'success',
                'account' => ['id', 'name', 'phone_number_id', 'status'],
                'profile',
                'health',
            ]);
    }

    public function test_user_can_update_whatsapp_business_profile(): void
    {
        $user = User::factory()->create();
        $business = WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Profile Test Business',
            'wallet_balance' => 50.00,
            'currency' => 'USD',
            'per_message_fee' => 0.0010,
            'bot_reply_fee' => 0.0005,
            'status' => 'active',
        ]);

        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'name' => 'Sales Line',
            'phone_number_id' => '123456789012345',
            'waba_id' => '987654321098765',
            'access_token' => 'EAAG_META_TEST_SANDBOX_TOKEN_DEMO',
            'status' => 'active',
        ]);

        $payload = [
            'about' => 'Available for inquiries and fast support.',
            'description' => 'We offer leading software and marketing solutions.',
            'address' => 'Cairo, Egypt',
            'email' => 'support@musoftwares.com',
            'vertical' => 'PROF_SERVICES',
            'websites' => ['https://musoftwares.com'],
        ];

        $response = $this->actingAs($user)->postJson("/whatsapp-sender/accounts/{$account->id}/profile", $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'WhatsApp Business Profile updated successfully on Meta Cloud API.',
            ]);

        $account->refresh();
        $this->assertEquals('Available for inquiries and fast support.', $account->metadata['business_profile']['about']);
        $this->assertEquals('support@musoftwares.com', $account->metadata['business_profile']['email']);
    }

    public function test_user_can_upload_and_delete_profile_photo(): void
    {
        $user = User::factory()->create();
        $business = WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Profile Test Business',
            'wallet_balance' => 50.00,
            'currency' => 'USD',
            'per_message_fee' => 0.0010,
            'bot_reply_fee' => 0.0005,
            'status' => 'active',
        ]);

        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'name' => 'Avatar Line',
            'phone_number_id' => '123456789012345',
            'waba_id' => '987654321098765',
            'access_token' => 'EAAG_META_TEST_SANDBOX_TOKEN_DEMO',
            'status' => 'active',
        ]);

        $fakePhoto = UploadedFile::fake()->image('avatar.jpg', 640, 640);

        $response = $this->actingAs($user)->postJson("/whatsapp-sender/accounts/{$account->id}/profile/photo", [
            'photo' => $fakePhoto,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $account->refresh();
        $this->assertNotEmpty($account->metadata['profile_picture_url']);

        // Delete photo
        $delResponse = $this->actingAs($user)->deleteJson("/whatsapp-sender/accounts/{$account->id}/profile/photo");
        $delResponse->assertOk()
            ->assertJson(['success' => true]);

        $account->refresh();
        $this->assertArrayNotHasKey('profile_picture_url', $account->metadata);
    }

    public function test_user_can_set_two_step_verification_pin(): void
    {
        $user = User::factory()->create();
        $business = WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'PIN Test Business',
            'wallet_balance' => 50.00,
            'currency' => 'USD',
            'per_message_fee' => 0.0010,
            'bot_reply_fee' => 0.0005,
            'status' => 'active',
        ]);

        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'name' => 'PIN Line',
            'phone_number_id' => '123456789012345',
            'waba_id' => '987654321098765',
            'access_token' => 'EAAG_META_TEST_SANDBOX_TOKEN_DEMO',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson("/whatsapp-sender/accounts/{$account->id}/pin", [
            'pin' => '123456',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $account->refresh();
        $this->assertTrue($account->metadata['has_2fa_pin'] ?? false);
    }

    public function test_user_can_sync_health_status(): void
    {
        $user = User::factory()->create();
        $business = WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Health Test Business',
            'wallet_balance' => 50.00,
            'currency' => 'USD',
            'per_message_fee' => 0.0010,
            'bot_reply_fee' => 0.0005,
            'status' => 'active',
        ]);

        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'name' => 'Health Line',
            'phone_number_id' => '123456789012345',
            'waba_id' => '987654321098765',
            'access_token' => 'EAAG_META_TEST_SANDBOX_TOKEN_DEMO',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson("/whatsapp-sender/accounts/{$account->id}/health-sync");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'health' => [
                    'quality_rating' => 'GREEN',
                ],
            ]);
    }
}
