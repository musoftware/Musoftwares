<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Currency;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\ServiceExtra;
use Modules\Marketplace\Services\MarketplaceTranslationService;
use Modules\Marketplace\Services\FreeDownloadService;

class ServiceCatalogCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_catalog_creation_with_packages_and_extras()
    {
        $seller = User::factory()->create();
        $currency = Currency::firstOrCreate(['id' => 1], ['currency' => 'USD', 'symbol' => '$', 'name' => 'US Dollar']);
        $category = ServiceCategory::create(['name' => 'Web Dev', 'slug' => 'web-dev']);

        $service = Service::create([
            'seller_id' => $seller->id,
            'title' => 'Custom Website Building',
            'category_id' => $category->id,
            'description' => 'Full stack web development service',
            'status' => 'active',
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic',
            'description' => 'Single Page Website',
            'price' => 150,
            'currency_id' => $currency->id,
            'delivery_days' => 3,
        ]);

        $extra = ServiceExtra::create([
            'service_id' => $service->id,
            'title' => 'Extra Fast 24h Delivery',
            'price' => 50,
            'duration_days' => 1,
        ]);

        $this->assertDatabaseHas('marketplace_services', [
            'seller_id' => $seller->id,
            'title' => 'Custom Website Building',
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('marketplace_packages', [
            'name' => 'Basic',
            'price' => 150,
        ]);
        $this->assertDatabaseHas('marketplace_service_extras', [
            'title' => 'Extra Fast 24h Delivery',
            'price' => 50,
        ]);
    }

    public function test_multi_language_translation_service()
    {
        $category = ServiceCategory::create(['name' => 'Design', 'slug' => 'design']);
        $service = Service::create([
            'seller_id' => User::factory()->create()->id,
            'title' => 'Logo Design',
            'category_id' => $category->id,
            'description' => 'Professional logo design',
            'status' => 'active',
        ]);

        $translationService = new MarketplaceTranslationService();
        $translationService->setTranslations($service, [
            'title' => ['ar' => 'تصميم شعار', 'en' => 'Logo Design'],
        ]);

        $service->refresh();
        $this->assertEquals('تصميم شعار', $translationService->getLocalizedTitle($service, 'ar'));
    }

    public function test_gated_free_download_lead_capture()
    {
        $category = ServiceCategory::create(['name' => 'Freebies', 'slug' => 'freebies']);
        $service = Service::create([
            'seller_id' => User::factory()->create()->id,
            'title' => 'Free Ebook',
            'category_id' => $category->id,
            'description' => 'Free guide download',
            'is_free' => true,
            'status' => 'active',
        ]);

        $downloadService = new FreeDownloadService();
        $download = $downloadService->processLeadDownload($service, 'lead@example.com', 'John Lead');

        $this->assertNotNull($download->download_token);
        $this->assertEquals('lead@example.com', $download->email);

        $claimed = $downloadService->verifyAndClaimDownload($download->download_token);
        $this->assertNotNull($claimed->downloaded_at);
    }

    public function test_service_gallery_upload_and_cover_image_accessor()
    {
        \Illuminate\Support\Facades\Storage::fake('public_uploads', ['url' => 'https://www.musoftwares.com/uploads']);

        $seller = User::factory()->create();
        $category = ServiceCategory::create(['name' => 'Design', 'slug' => 'design-test']);
        $currency = Currency::firstOrCreate(['id' => 1], ['currency' => 'USD', 'symbol' => '$', 'name' => 'US Dollar']);

        $file = \Illuminate\Http\UploadedFile::fake()->image('cover.jpg');

        $response = $this->actingAs($seller)->post(route('marketplace.services.store'), [
            'title' => 'Graphic Design Service Test',
            'category_id' => $category->id,
            'description' => 'Professional graphic design service test description',
            'gallery' => [$file],
            'packages' => [
                [
                    'name' => 'Basic',
                    'description' => 'Basic package',
                    'price' => 100,
                    'currency_id' => $currency->id,
                    'delivery_days' => 2,
                ]
            ]
        ]);

        $response->assertRedirect();

        $service = Service::where('title', 'Graphic Design Service Test')->first();
        $this->assertNotNull($service);
        $this->assertNotEmpty($service->gallery);

        // Assert file exists on public_uploads disk
        \Illuminate\Support\Facades\Storage::disk('public_uploads')->assertExists($service->gallery[0]);

        // Assert cover_image accessor generates valid URL using public_uploads disk
        $coverUrl = $service->cover_image;
        $this->assertStringContainsString('/uploads/', $coverUrl);
    }
}
