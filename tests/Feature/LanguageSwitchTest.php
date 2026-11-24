<?php

namespace Tests\Feature;

use App\Services\IpGeolocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The language choice was only in the footer. It is now in the header (desktop), the phone menu
 * and the footer, and it opens the same page with the same query in the other language.
 */
class LanguageSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_blade_header_menu_and_footer_link_to_the_same_page_in_the_other_language(): void
    {
        $html = $this->get('/portfolio?lang=ar&ref=abc')->assertOk()->getContent();

        $this->assertStringContainsString('<html lang="ar" dir="rtl"', $html);
        preg_match_all('~<a href="([^"]+)" lang="en" hreflang="en"~', $html, $links);
        $this->assertCount(3, $links[1], 'desktop header, phone menu, footer');
        foreach ($links[1] as $url) {
            parse_str((string) parse_url(html_entity_decode($url), PHP_URL_QUERY), $query);
            $this->assertSame('/portfolio', parse_url(html_entity_decode($url), PHP_URL_PATH));
            $this->assertSame(['lang' => 'en', 'ref' => 'abc'], $query);
        }
    }

    public function test_switching_sets_language_and_direction_and_is_remembered(): void
    {
        $this->get('/?lang=en')->assertOk()->assertSee('<html lang="en" dir="ltr"', false);
        $this->get('/')->assertOk()->assertSee('<html lang="en" dir="ltr"', false);
        $this->get('/?lang=ar')->assertOk()->assertSee('<html lang="ar" dir="rtl"', false);
        $this->get('/')->assertOk()->assertSee('<html lang="ar" dir="rtl"', false);
    }

    public function test_inertia_pages_get_the_switch_for_the_header(): void
    {
        $this->get('/estimator?lang=en')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('siteNav.language_switch.lang', 'ar')
            ->where('siteNav.language_switch.label', 'العربية')
            ->where('siteFooter.language_switch.lang', 'ar')
        );
    }

    public function test_no_switch_when_the_language_is_fixed_for_the_visitor(): void
    {
        $this->app->instance(IpGeolocationService::class, new class extends IpGeolocationService
        {
            public function getCountryFromIp($ip)
            {
                return 'EG';
            }
        });

        $html = $this->get('/?lang=en')->assertOk()->getContent();
        $this->assertStringContainsString('<html lang="ar" dir="rtl"', $html);
        $this->assertStringNotContainsString('hreflang="en" class', $html);
    }
}
