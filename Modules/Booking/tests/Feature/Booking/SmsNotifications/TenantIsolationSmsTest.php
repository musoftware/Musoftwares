<?php

namespace Modules\Booking\tests\Feature\Booking\SmsNotifications;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Modules\Booking\app\Features\SmsNotifications\Models\SmsTemplate;
use Modules\Booking\app\Features\SmsNotifications\Models\SmsSetting;

class TenantIsolationSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_cannot_view_other_tenant_templates()
    {
        $user1 = User::factory()->create(['tenant_id' => 1]);
        $user2 = User::factory()->create(['tenant_id' => 2]);

        SmsTemplate::create(['tenant_id' => 1, 'type' => 'confirmation', 'content' => 'T1 Conf']);
        SmsTemplate::create(['tenant_id' => 2, 'type' => 'confirmation', 'content' => 'T2 Conf']);

        $response = $this->actingAs($user1)->getJson('/api/sms-templates');
        
        $response->assertStatus(200)->assertJsonCount(1);
        $this->assertEquals('T1 Conf', $response->json()[0]['content']);
    }

    public function test_tenant_credentials_are_encrypted()
    {
        $user = User::factory()->create(['tenant_id' => 1]);

        $setting = SmsSetting::create([
            'tenant_id' => 1,
            'provider_name' => 'twilio',
            'provider_credentials' => ['token' => 'super_secret']
        ]);

        // Fetch raw directly from DB ignoring casts to verify it is encrypted
        $rawSetting = \DB::table('booking_sms_settings')->where('id', $setting->id)->first();
        
        $this->assertNotEquals('{"token":"super_secret"}', $rawSetting->provider_credentials);
        $this->assertStringContainsString('eyJ', $rawSetting->provider_credentials); // Laravel payload prefix
        
        // Eloquent should decrypt automatically
        $this->assertEquals('super_secret', $setting->fresh()->provider_credentials['token']);
    }
}
