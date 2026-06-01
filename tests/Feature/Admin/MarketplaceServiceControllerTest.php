<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sellerUser;
    protected ServiceCategory $category;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->sellerUser = User::factory()->create(['onboarding_completed' => true]);

        $this->category = ServiceCategory::create([
            'name' => 'Web Dev',
            'slug' => 'web-dev'
        ]);

        $this->service = Service::create([
            'seller_id' => $this->sellerUser->id,
            'category_id' => $this->category->id,
            'title' => 'My Service',
            'description' => 'A great service',
            'status' => 'draft',
        ]);
    }

    public function test_admin_can_view_all_services(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/marketplace/all-services');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_all_services(): void
    {
        $response = $this->actingAs($this->sellerUser)->get('/admin/marketplace/all-services');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_pending_services(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/marketplace/pending-services');
        $response->assertStatus(200);
    }

    public function test_admin_can_approve_service(): void
    {
        $response = $this->actingAs($this->admin)->post("/admin/marketplace/services/{$this->service->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('active', $this->service->fresh()->status);
    }

    public function test_admin_can_reject_service(): void
    {
        $response = $this->actingAs($this->admin)->post("/admin/marketplace/services/{$this->service->id}/reject");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('rejected', $this->service->fresh()->status);
    }

    public function test_admin_can_suspend_service(): void
    {
        $response = $this->actingAs($this->admin)->post("/admin/marketplace/services/{$this->service->id}/suspend");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('suspended', $this->service->fresh()->status);
    }

    public function test_admin_can_feature_service(): void
    {
        $this->assertFalse((bool) $this->service->is_featured);
        $response = $this->actingAs($this->admin)->post("/admin/marketplace/services/{$this->service->id}/feature");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue((bool) $this->service->fresh()->is_featured);
    }

    public function test_admin_can_view_edit_service(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/marketplace/services/{$this->service->id}/edit");
        $response->assertStatus(200);
    }

    public function test_admin_can_update_service(): void
    {
        $response = $this->actingAs($this->admin)->put("/admin/marketplace/services/{$this->service->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated Desc',
            'category_id' => $this->category->id,
            'status' => 'active',
            'packages' => [
                [
                    'name' => 'Package 1',
                    'description' => 'Pack desc',
                    'price' => 10,
                    'delivery_days' => 2
                ]
            ]
        ]);

        $response->assertRedirect(route('admin.marketplace.services.all'));
        $response->assertSessionHas('success');
        $this->assertEquals('Updated Title', $this->service->fresh()->title);
        $this->assertCount(1, $this->service->packages);
    }

    public function test_admin_can_delete_service(): void
    {
        $response = $this->actingAs($this->admin)->delete("/admin/marketplace/services/{$this->service->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('marketplace_services', ['id' => $this->service->id]);
    }
}
