<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureSubscriptionIsActive;
use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\ServiceOrder;
use Tests\TestCase;

class MarketplaceProjectKickoffTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;
    protected User $seller;
    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureSubscriptionIsActive::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->seed(RolesAndPermissionsSeeder::class);

        $usdCurrency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['symbol' => '$', 'string_format' => '$%01.2f']
        );

        $this->buyer = User::factory()->create(['onboarding_completed' => true, 'currency_id' => $usdCurrency->id]);
        $this->buyer->assignRole('client');

        $this->seller = User::factory()->create(['onboarding_completed' => true, 'currency_id' => $usdCurrency->id]);
        $this->seller->assignRole('client');

        $this->category = ServiceCategory::create([
            'name' => 'Web Development',
            'slug' => 'web-development',
            'description' => 'PHP, Laravel, React development',
        ]);
    }

    public function test_purchase_service_automatically_kicks_off_project_and_tasks(): void
    {
        $this->withoutExceptionHandling();
        // 1. Create a service and a package
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Custom Web App Development',
            'description' => 'A custom built web application using React and Laravel.',
            'status' => 'active',
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Fullstack Package',
            'description' => 'A complete custom website',
            'price' => 300.00,
            'currency_id' => 1,
            'delivery_days' => 8,
        ]);

        // 2. Fund the buyer's wallet
        $this->buyer->user_balance = 500.00;
        $this->buyer->save();

        // 3. Purchase the service package
        $response = $this->actingAs($this->buyer)
            ->post(route('marketplace.orders.store'), [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(302);

        // 4. Assert order exists
        $order = ServiceOrder::where('buyer_id', $this->buyer->id)->first();
        $this->assertNotNull($order);

        // 5. Assert project was created by the listener
        $this->assertDatabaseHas('projects', [
            'user_id' => $this->buyer->id,
            'project_name' => "Order #{$order->id}: Custom Web App Development (Fullstack Package)",
            'budget' => 300.00,
            'status' => 'open',
            'archived' => 0,
        ]);

        $project = \App\Models\Project::where('user_id', $this->buyer->id)->first();
        $this->assertNotNull($project);

        // 6. Assert standard tasks are seeded for this project
        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'task_name' => 'Kickoff & Requirement Alignment',
            'priority' => 'high',
        ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'task_name' => 'Design & UI/UX Review',
            'priority' => 'medium',
        ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'task_name' => 'Development & Staging Setup',
            'priority' => 'high',
        ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'task_name' => 'Final QA & Delivery Verification',
            'priority' => 'high',
        ]);
        
        $this->assertEquals(4, $project->tasks()->count());
    }
}
