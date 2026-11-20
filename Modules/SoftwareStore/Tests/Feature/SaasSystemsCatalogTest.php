<?php

namespace Modules\SoftwareStore\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Database\Seeders\SystemsCatalogExtra;
use Modules\SoftwareStore\Database\Seeders\SystemsCatalogSaas;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

/** The six multi-company systems: same page blocks as the others, real screenshots in both languages, limits stated. */
class SaasSystemsCatalogTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    private const SLUGS = ['travel-agency-system', 'crm-accounting-system', 'private-school-system', 'contracting-system', 'law-office-system', 'fleet-system'];

    public function test_each_system_has_the_same_copy_blocks_and_an_icon(): void
    {
        $reference = SystemsCatalogExtra::systems()[0];

        $this->assertSame(self::SLUGS, array_column(SystemsCatalogSaas::systems(), 'slug'));
        foreach (SystemsCatalogSaas::systems() as $system) {
            foreach (['ar', 'en'] as $lang) {
                foreach (array_keys($reference[$lang]) as $key) {
                    $this->assertArrayHasKey($key, $system[$lang], "{$system['slug']} $lang is missing $key");
                }
                $this->assertCount(3, $system[$lang]['problems']);
                $this->assertCount(3, $system[$lang]['showcase']);
                $this->assertCount(4, $system[$lang]['pillars']);
                $this->assertCount(3, $system[$lang]['metrics']);
            }
            $this->assertFileExists(public_path("images/software/{$system['slug']}.svg"));
        }
    }

    public function test_a_new_product_gets_the_arabic_and_english_screenshots_hidden_and_unpriced(): void
    {
        $this->seedUsdEgpRates();
        $this->seed(SoftwareProductSeeder::class);

        foreach (self::SLUGS as $slug) {
            $product = SoftwareProduct::where('slug', $slug)->firstOrFail();
            $this->assertFalse($product->is_active, $slug);
            $this->assertSame(0.0, (float) $product->price, $slug);
            $this->assertSame([5000, 20000], array_column($product->plans, 'price'), $slug);
            $this->assertCount(4, $product->galleryPaths(), $slug);
            $this->assertCount(4, $product->theme['gallery_en'], $slug);

            foreach ([...$product->galleryPaths(), ...$product->theme['gallery_en']] as $path) {
                $this->assertFileExists(public_path($path));
            }
            $this->assertStringEndsWith("/images/software/{$slug}/1.webp", $product->galleryUrlsFor('ar')[0]);
            $this->assertStringEndsWith("/images/software/{$slug}/en-1.webp", $product->galleryUrlsFor('en')[0]);
        }
    }

    public function test_seeding_again_keeps_the_pictures_the_admin_uploaded(): void
    {
        $this->seedUsdEgpRates();
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::where('slug', 'fleet-system')->firstOrFail()->update(['gallery' => ['software-products/fleet-system/own.png']]);

        $this->seed(SoftwareProductSeeder::class);

        $this->assertSame(['software-products/fleet-system/own.png'], SoftwareProduct::where('slug', 'fleet-system')->first()->gallery);
    }

    public function test_the_pages_state_the_limits_and_never_show_demo_logins_or_emojis(): void
    {
        $text = json_encode(SystemsCatalogSaas::systems(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach (['not against a real account', 'not certified by ETA', 'must check every row', 'no connection to global booking systems', 'sends nothing to the Egyptian Tax Authority', 'no route planning', 'fictional'] as $limit) {
            $this->assertStringContainsStringIgnoringCase($limit, $text, $limit);
        }
        $this->assertStringNotContainsString('.test', $text);
        $this->assertStringNotContainsString('password', strtolower($text));
        $this->assertSame(0, preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $text), 'no emojis');
    }
}
