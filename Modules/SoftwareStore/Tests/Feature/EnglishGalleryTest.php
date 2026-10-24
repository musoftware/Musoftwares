<?php

namespace Modules\SoftwareStore\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnglishGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function productWithBothGalleries(): SoftwareProduct
    {
        $this->seed(SoftwareProductSeeder::class);
        $product = SoftwareProduct::where('slug', 'pharmacy-system')->firstOrFail();
        $theme = $product->theme ?? [];
        $theme['gallery_en'] = ['software-products/pharmacy-system/en-01.png', 'software-products/pharmacy-system/en-02.png'];
        $product->update([
            'is_active' => true,
            'gallery' => ['software-products/pharmacy-system/shot-01.png', 'software-products/pharmacy-system/shot-02.png'],
            'theme' => $theme,
        ]);

        return $product->fresh();
    }

    public function test_english_uses_the_english_pictures_and_other_languages_keep_the_normal_gallery(): void
    {
        $product = $this->productWithBothGalleries();

        $this->assertSame([asset('uploads/software-products/pharmacy-system/en-01.png'), asset('uploads/software-products/pharmacy-system/en-02.png')], $product->galleryUrlsFor('en'));
        $this->assertSame($product->galleryUrls(), $product->galleryUrlsFor('ar'));
        $this->assertStringContainsString('shot-01.png', $product->galleryUrlsFor('ar')[0]);
    }

    public function test_a_product_without_english_pictures_falls_back_to_the_normal_gallery(): void
    {
        $product = $this->productWithBothGalleries();
        $theme = $product->theme;
        unset($theme['gallery_en']);
        $product->update(['theme' => $theme]);

        $this->assertSame($product->fresh()->galleryUrls(), $product->fresh()->galleryUrlsFor('en'));
    }

    public function test_the_locale_of_the_request_decides_when_no_language_is_given(): void
    {
        $product = $this->productWithBothGalleries();

        app()->setLocale('en');
        $this->assertStringContainsString('en-01.png', $product->galleryUrlsFor()[0]);

        app()->setLocale('ar');
        $this->assertStringContainsString('shot-01.png', $product->galleryUrlsFor()[0]);
    }

    public function test_the_product_page_and_the_homepage_show_the_picture_of_the_visitor_language(): void
    {
        $this->productWithBothGalleries();

        $this->get('/services/pharmacy-system?lang=en')->assertOk()
            ->assertSee('uploads/software-products/pharmacy-system/en-01.png', false)
            ->assertDontSee('uploads/software-products/pharmacy-system/shot-01.png', false);

        $this->get('/services/pharmacy-system?lang=ar')->assertOk()
            ->assertSee('uploads/software-products/pharmacy-system/shot-01.png', false)
            ->assertDontSee('uploads/software-products/pharmacy-system/en-01.png', false);

        $this->get('/?lang=en')->assertOk()->assertSee('uploads/software-products/pharmacy-system/en-01.png', false);
        $this->get('/?lang=ar')->assertOk()->assertSee('uploads/software-products/pharmacy-system/shot-01.png', false);
    }

    public function test_saving_the_product_in_the_admin_keeps_the_english_pictures(): void
    {
        $product = $this->productWithBothGalleries();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post("/admin/software-products/{$product->id}", [
            'name_en' => 'Pharmacy', 'name_ar' => 'صيدلية', 'price' => 5000, 'currency' => 'EGP', 'is_active' => 1,
            'colors' => ['accent' => '#112233', 'ink' => '#000000'],
            'sections' => ['pillars'],
        ])->assertSessionHasNoErrors();

        $this->assertCount(2, $product->fresh()->theme['gallery_en']);
        $this->assertSame('#112233', $product->fresh()->themeColors()['accent']);
    }
}
