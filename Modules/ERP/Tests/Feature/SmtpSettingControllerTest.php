<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\SmtpSetting;
use Tests\TestCase;

class SmtpSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_smtp_edit_loads_settings()
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp-smtp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        SmtpSetting::create([
            'tenant_id' => $tenant->id,
            'host' => 'smtp.mailtrap.io',
            'port' => 2525,
            'username' => 'testuser',
            'password' => 'testpass',
            'encryption' => 'tls',
            'from_address' => 'noreply@acmecorp.com',
            'from_name' => 'Acme Corp',
        ]);

        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->get('/erp/settings/smtp');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ERP/Settings/Smtp')
            ->has('smtp')
            ->where('smtp.host', 'smtp.mailtrap.io')
            ->where('smtp.password', '********')
        );
    }

    public function test_smtp_update_saves_settings()
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp-smtp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->put('/erp/settings/smtp', [
            'host' => 'smtp.mailgun.org',
            'port' => 587,
            'username' => 'postmaster@acmecorp.com',
            'password' => 'newpassword',
            'encryption' => 'ssl',
            'from_address' => 'hello@acmecorp.com',
            'from_name' => 'Acme Corp LLC',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', __('erp.smtp_settings_updated'));

        $smtp = SmtpSetting::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($smtp);
        $this->assertEquals('smtp.mailgun.org', $smtp->host);
        $this->assertEquals(587, $smtp->port);
        $this->assertEquals('newpassword', $smtp->password);
    }
}
