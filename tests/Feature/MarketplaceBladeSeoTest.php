<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServicePackage;
use Tests\TestCase;

class MarketplaceBladeSeoTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected ServiceCategory $category;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'name' => 'John Developer',
            'onboarding_completed' => true,
        ]);

        $this->category = ServiceCategory::create([
            'name' => 'Web Development',
            'slug' => 'web-development',
            'description' => 'Professional Web Development and Cloud Solutions',
        ]);

        $this->service = Service::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Custom Laravel Web Application Development',
            'slug' => 'custom-laravel-web-application-development',
            'tagline' => 'High-performance bespoke Laravel applications built for scale.',
            'description' => 'We design and develop production-ready web applications using Laravel, MySQL, and Tailwind CSS with full responsiveness.',
            'status' => 'active',
            'gallery' => ['services/1/laravel-app.jpg'],
        ]);

        ServicePackage::create([
            'service_id' => $this->service->id,
            'name' => 'Standard Package',
            'description' => 'Complete Laravel setup and source code.',
            'price' => 150.00,
            'delivery_days' => 5,
            'revisions' => 3,
        ]);
    }

    public function test_marketplace_services_index_renders_blade_with_seo_tags(): void
    {
        $response = $this->get(route('marketplace.services.index'));

        $response->assertStatus(200);
        $response->assertViewIs('marketplace::public.index');
        $response->assertSee('Custom Laravel Web Application Development');
        $response->assertSee('application/ld+json', false);
        $response->assertSee('canonical', false);
        $response->assertSee('og:title', false);
        $response->assertSee('og:description', false);
        $response->assertSee('Web Development');
    }

    public function test_marketplace_service_show_renders_blade_with_schema_and_meta(): void
    {
        $response = $this->get(route('marketplace.services.show', [
            'id' => $this->service->id,
            'slug' => $this->service->slug,
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('marketplace::public.show');
        $response->assertSee('Custom Laravel Web Application Development');
        $response->assertSee('John Developer');
        $response->assertSee('$150.00');
        $response->assertSee('application/ld+json', false);
        $response->assertSee('schema.org', false);
        $response->assertSee('BreadcrumbList', false);
        $response->assertSee('canonical', false);
    }

    public function test_marketplace_category_page_renders_blade(): void
    {
        $response = $this->get(route('marketplace.categories.show', ['slug' => $this->category->slug]));

        $response->assertStatus(200);
        $response->assertViewIs('marketplace::public.index');
        $response->assertSee('Web Development');
        $response->assertSee('Custom Laravel Web Application Development');
    }
}
