<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SiteHeaderTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = ['/', '/services', '/portfolio', '/platforms', '/solutions', '/library'];

    public function test_every_public_page_shows_the_same_header_with_a_more_menu(): void
    {
        foreach (self::PAGES as $page) {
            $html = $this->get($page.'?lang=ar')->assertOk()->getContent();

            $this->assertStringContainsString('id="more-menu"', $html, $page);
            $this->assertStringContainsString('id="site-mobile-menu"', $html, $page);
            $this->assertStringContainsString('المزيد', $html, $page);
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
        $this->assertStringContainsString('Privacy policy', $english);
    }

    public function test_react_public_pages_get_the_same_menu_links_from_the_server(): void
    {
        $this->get('/estimator?lang=ar')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('siteNav.main', 5)
                ->has('siteNav.more', 4)
                ->where('siteNav.main.0.url', '/services')
        );
    }
}
