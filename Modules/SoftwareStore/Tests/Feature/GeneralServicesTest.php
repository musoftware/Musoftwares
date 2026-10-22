<?php

namespace Modules\SoftwareStore\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SoftwareStore\Database\Seeders\GeneralServicesCatalog;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Tests\TestCase;

class GeneralServicesTest extends TestCase
{
    use RefreshDatabase;

    private const SLUGS = [
        'company-website-design', 'landing-page-design', 'online-store-development', 'custom-web-app-development',
        'website-care-plans', 'social-media-management', 'paid-ads-management', 'logo-and-brand-identity', 'ai-chatbot-whatsapp-automation',
    ];

    private function seedServices(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::whereIn('slug', self::SLUGS)->update(['is_active' => true]);
    }

    public function test_every_general_service_has_three_priced_plans_with_bilingual_lists(): void
    {
        $programs = GeneralServicesCatalog::programs();

        $this->assertSame(self::SLUGS, array_column($programs, 'slug'));
        foreach ($programs as $program) {
            $this->assertCount(3, $program['plans'], $program['slug']);
            foreach ($program['plans'] as $plan) {
                $this->assertGreaterThan(0, $plan['price'], $program['slug']);
                $this->assertNotEmpty($plan['features'], $program['slug']);
                $this->assertCount(count($plan['features']), $plan['features_ar'], $program['slug'].' needs the same lines in Arabic');
            }
            $this->assertFileExists(public_path($program['image_path']));
        }
    }

    public function test_services_start_hidden_until_the_owner_turns_them_on(): void
    {
        $this->seed(SoftwareProductSeeder::class);

        $this->assertSame(0, SoftwareProduct::whereIn('slug', self::SLUGS)->where('is_active', true)->count());
        $this->get('/services/company-website-design')->assertNotFound();
    }

    public function test_service_pages_show_plan_lists_exclusions_and_refund_terms_in_both_languages(): void
    {
        $this->seedServices();

        $english = $this->withSession(['locale' => 'en'])->get('/services/company-website-design?lang=en')->assertOk();
        $english->assertSee('Up to 5 pages in one language')->assertSee('Not included:')->assertSee('Refunds:')->assertSee('9,000', false);

        $arabic = $this->withSession(['locale' => 'ar'])->get('/services/company-website-design?lang=ar')->assertOk();
        $arabic->assertSee('حتى 5 صفحات بلغة واحدة')->assertSee('غير مشمول')->assertSee('الاسترداد');
    }

    public function test_service_pages_use_service_schema_without_ratings_or_guarantees(): void
    {
        $this->seedServices();

        foreach (self::SLUGS as $slug) {
            $html = $this->get("/services/{$slug}?lang=en")->assertOk()->getContent();

            preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $matches);
            $storeGraph = collect($matches[1])->first(fn ($json) => str_contains($json, '"@graph"'));
            $this->assertStringContainsString('"@type": "Service"', json_encode(json_decode($storeGraph, true), JSON_PRETTY_PRINT), $slug);
            $this->assertStringNotContainsString('SoftwareApplication', $storeGraph, $slug);
            $this->assertStringNotContainsString('aggregateRating', $storeGraph, $slug);
        }
    }

    public function test_markdown_copy_lists_the_plan_contents(): void
    {
        $this->seedServices();

        $this->get('/services/social-media-management.md?lang=en')->assertOk()
            ->assertSee('8 designed posts a month')->assertSee('Starter');
    }

    public function test_service_order_requires_choosing_a_plan(): void
    {
        $this->seedServices();

        $this->post('/services/landing-page-design/order', ['client_name' => 'Sara', 'client_email' => 'sara@example.com', 'client_whatsapp' => '+201000000000'])
            ->assertSessionHasErrors('plan_key');
    }

    public function test_legacy_company_service_urls_still_resolve(): void
    {
        $this->seedServices();

        $this->assertNotSame(500, $this->get('/services/full-stack-web-development-1')->getStatusCode());
        $this->assertNotSame(500, $this->get('/services/code-review-technical-auditing')->getStatusCode());
    }

    public function test_services_page_groups_products_into_programs_systems_and_services(): void
    {
        $this->seedServices();
        SoftwareProduct::query()->update(['is_active' => true]);

        $html = $this->get('/services?lang=en')->assertOk()->getContent();

        foreach (['programs', 'systems', 'services'] as $group) {
            $this->assertStringContainsString('id="'.$group.'"', $html);
        }
        $this->assertSame('services', SoftwareProduct::where('slug', 'landing-page-design')->firstOrFail()->group());
        $this->assertSame('systems', SoftwareProduct::where('slug', 'pharmacy-system')->firstOrFail()->group());
        $this->assertSame('programs', SoftwareProduct::where('slug', 'maps-leads-pro')->firstOrFail()->group());
    }
}
