<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Services\FreeTools;
use Tests\TestCase;

class SiteHeaderTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = ['/', '/services', '/how-we-work', '/portfolio', '/library'];

    public function test_every_public_page_shows_the_same_header_with_the_all_services_menu(): void
    {
        foreach (self::PAGES as $page) {
            $html = $this->get($page.'?lang=ar')->assertOk()->getContent();

            $this->assertStringContainsString('id="mega-menu"', $html, $page);
            $this->assertStringContainsString('id="site-mobile-menu"', $html, $page);
            $this->assertStringContainsString('كل الخدمات', $html, $page);
            $this->assertSame(1, substr_count($html, '<header'), $page.' must have one header');
        }
    }

    public function test_main_links_point_to_the_new_pages_and_the_old_home_only_header_is_gone(): void
    {
        $html = $this->get('/?lang=en')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('services.index').'"', $html);
        $this->assertStringContainsString('href="/portfolio"', $html);
        $this->assertStringNotContainsString('mobile-home-menu', $html);
        $this->assertStringNotContainsString('STUDIO', $html);
    }

    public function test_a_signed_in_user_still_sees_the_homepage_with_a_dashboard_link(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/?lang=en')->assertOk()->assertSee('href="/dashboard"', false);
    }

    public function test_the_footer_follows_the_language_of_the_page(): void
    {
        $arabic = $this->get('/?lang=ar')->assertOk()->getContent();
        $english = $this->get('/?lang=en')->assertOk()->getContent();

        $this->assertStringContainsString('سياسة الخصوصية', $arabic);
        $this->assertStringNotContainsString('Sovereignty', $arabic);
        $this->assertStringContainsString('Data Protection Officer', $english);
    }

    public function test_react_public_pages_get_the_same_menu_links_from_the_server(): void
    {
        $this->get('/estimator?lang=ar')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('siteNav.main', 5)
                ->where('siteNav.main.0.url', '/marketplace/catalog')
                ->where('siteNav.all_url', '/services')
                ->where('siteNav.mega.0.links.0.url', '/marketplace/catalog')
        );
    }

    public function test_the_menu_shows_the_marketplace_and_every_active_product_and_free_tool(): void
    {
        $visible = SoftwareProduct::create(['slug' => 'menu-test-system', 'name_en' => 'Menu test system', 'name_ar' => 'نظام اختبار القائمة', 'is_active' => true]);
        $hidden = SoftwareProduct::create(['slug' => 'hidden-test-system', 'name_en' => 'Hidden system', 'name_ar' => 'نظام مخفي', 'is_active' => false]);

        $html = $this->get('/?lang=ar')->assertOk()->getContent();

        $this->assertStringContainsString('href="/marketplace/catalog"', $html);
        $this->assertStringContainsString('href="/services/'.$visible->slug.'"', $html);
        $this->assertStringNotContainsString('href="/services/'.$hidden->slug.'"', $html);

        foreach (FreeTools::slugs() as $slug) {
            $this->assertStringContainsString('href="/services/tools/'.$slug.'"', $html, $slug);
        }
    }
}
