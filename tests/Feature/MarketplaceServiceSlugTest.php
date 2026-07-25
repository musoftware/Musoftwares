<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Tests\TestCase;

class MarketplaceServiceSlugTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected ServiceCategory $category;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create(['onboarding_completed' => true]);

        $this->category = ServiceCategory::create([
            'name' => 'Web Development',
            'slug' => 'web-development',
        ]);

        $this->service = Service::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Custom WhatsApp Bot Development',
            'slug' => 'custom-whatsapp-bot-development',
            'description' => 'Professional WhatsApp bot automation service with full customization options.',
            'status' => 'active',
            'gallery' => ['services/1/sample.jpg'],
        ]);
    }

    public function test_service_has_slug_attribute_and_column(): void
    {
        $this->assertEquals('custom-whatsapp-bot-development', $this->service->slug);
        $this->assertDatabaseHas('marketplace_services', [
            'id' => $this->service->id,
            'slug' => 'custom-whatsapp-bot-development',
        ]);
    }

    public function test_can_access_service_by_id(): void
    {
        $response = $this->get('/marketplace/services/' . $this->service->id);
        $response->assertStatus(200);
    }

    public function test_can_access_service_by_id_and_slug(): void
    {
        $response = $this->get('/marketplace/services/' . $this->service->id . '/custom-whatsapp-bot-development');
        $response->assertStatus(200);
    }

    public function test_can_access_service_by_hyphenated_id_and_slug(): void
    {
        $response = $this->get('/marketplace/services/' . $this->service->id . '-custom-whatsapp-bot-development');
        $response->assertStatus(200);
    }

    public function test_can_access_service_by_standalone_slug(): void
    {
        $response = $this->get('/marketplace/services/custom-whatsapp-bot-development');
        $response->assertStatus(200);
    }

    public function test_admin_publishing_service_is_auto_approved(): void
    {
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->post(route('marketplace.services.store'), [
                'title' => 'Admin Direct Published Service',
                'description' => 'Detailed description for admin published marketplace service item exceeding 100 chars requirement.',
                'category_id' => $this->category->id,
                'packages' => [
                    [
                        'name' => 'Full Package',
                        'description' => 'Complete package details',
                        'price' => 250.00,
                        'currency_id' => 1,
                        'delivery_days' => 2,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('marketplace_services', [
            'title' => 'Admin Direct Published Service',
            'status' => 'active',
        ]);
    }
}
