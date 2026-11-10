<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SiteStores;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Services\FreeTools;
use Tests\TestCase;

class SiteHeaderTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = ['/', '/services', '/how-we-work', '/portfolio', '/library'];

    public function test_every_public_page_shows_the_same_header_with_the_services_and_stores_menus(): void
    {
        foreach (self::PAGES as $page) {
            $html = $this->get($page.'?lang=ar')->assertOk()->getContent();

            $this->assertStringContainsString('id="mega-menu"', $html, $page);
            $this->assertStringContainsString('id="stores-menu"', $html, $page);
            $this->assertStringContainsString('id="site-mobile-menu"', $html, $page);
            $this->assertStringContainsString('خدماتنا', $html, $page);
            $this->assertStringContainsString('متاجرنا', $html, $page);
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
                ->has('siteNav.main', 3)
                ->where('siteNav.main.0.url', '/portfolio')
                ->where('siteNav.mega.0.key', 'custom')
                ->where('siteNav.mega.0.links.0.url', '/start-project')
                ->where('siteNav.intro.url', '/start-project')
                ->has('siteNav.stores', 4)
                ->where('siteNav.stores.0.name', 'شغلانة')
                ->where('siteNav.stores.1.url', '/store')
        );
    }

    public function test_custom_work_comes_first_and_every_menu_says_what_we_do(): void
    {
        $html = $this->get('/?lang=ar')->assertOk()->getContent();

        $this->assertStringContainsString('إحنا شركة برمجة', $html);
        $this->assertStringContainsString('href="/start-project"', $html);
        $this->assertLessThan(strpos($html, 'أدوات مجانية'), strpos($html, 'برمجة على مقاس شغلك'));
    }

    public function test_the_stores_menu_shows_each_store_with_its_picture(): void
    {
        $html = $this->get('/?lang=ar')->assertOk()->getContent();

        foreach (SiteStores::cards('ar') as $store) {
            $this->assertStringContainsString('href="'.$store['url'].'"', $html, $store['key']);
            $this->assertStringContainsString($store['name'], $html, $store['key']);
            $this->assertFileExists(public_path(ltrim($store['image'], '/')), $store['key']);
        }
    }

    public function test_the_menu_shows_every_active_product_and_links_to_all_free_tools(): void
    {
        $visible = SoftwareProduct::create(['slug' => 'menu-test-system', 'name_en' => 'Menu test system', 'name_ar' => 'نظام اختبار القائمة', 'is_active' => true]);
        $hidden = SoftwareProduct::create(['slug' => 'hidden-test-system', 'name_en' => 'Hidden system', 'name_ar' => 'نظام مخفي', 'is_active' => false]);

        $html = $this->get('/?lang=ar')->assertOk()->getContent();

        $this->assertStringContainsString('href="/marketplace/catalog"', $html);
        $this->assertStringContainsString('href="/services/'.$visible->slug.'"', $html);
        $this->assertStringNotContainsString('href="/services/'.$hidden->slug.'"', $html);

        $this->assertStringContainsString('href="/services/tools/'.FreeTools::slugs()[0].'"', $html);
        $this->assertStringContainsString('href="/services/tools"', $html);
        $this->assertStringContainsString('شوف الكل ('.count(FreeTools::slugs()).')', $html);
    }
}
