<?php

namespace Modules\ERP\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Project;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\ERPTask;
use Modules\ERP\Models\TenantClient;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Spatie permissions require this to bypass caching issues in testing
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_user_without_calendar_addon_cannot_view_calendar()
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $owner->id, 'name' => 'Test', 'status' => 'active']);

        $response = $this->actingAs($owner)->get('/erp/calendar');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('ERP/UpgradePreview'));
    }

    public function test_user_with_calendar_addon_can_view_calendar()
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $owner->id, 'name' => 'Test', 'status' => 'active']);

        \App\Models\UserSubscription::create([
            'user_id' => $owner->id,
            'object' => 'erp-calendar',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($owner)->get('/erp/calendar');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('ERP/Calendar/Index'));
    }
}
