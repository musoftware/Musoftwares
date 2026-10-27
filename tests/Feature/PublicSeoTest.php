<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    private const GOOGLEBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    public function test_search_engines_get_the_same_html_page_as_visitors(): void
    {
        $response = $this->withHeader('User-Agent', self::GOOGLEBOT)->get('/estimator');

        $response->assertOk();
        $this->assertStringStartsWith('text/html', $response->headers->get('Content-Type'));
    }

    public function test_home_page_title_is_the_page_title_not_a_section_heading(): void
    {
        $this->get('/?lang=en')->assertOk()->assertSee('<title>Musoftware | Ready-made systems', false);
        $this->get('/?lang=ar')->assertOk()->assertSee('<title>موسوفت | أنظمة وبرامج جاهزة', false);
    }

    public function test_fixed_pages_have_a_title_and_description_in_the_page_language(): void
    {
        $this->get('/platforms?lang=ar')->assertOk()
            ->assertSee('<title>'.e(__('seo.platforms.title', [], 'ar')).' | موسوفت</title>', false)
            ->assertSee('<meta name="description" content="'.e(__('seo.platforms.description', [], 'ar')).'">', false);

        $this->get('/company/contact?lang=en')->assertOk()
            ->assertSee('<title>'.e(__('seo.contact.title', [], 'en')).' | Musoftware</title>', false);
    }

    public function test_english_page_points_to_itself_and_to_its_arabic_version(): void
    {
        $html = $this->get('/platforms?lang=en')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.url('/platforms').'">', $html);
        $this->assertStringContainsString('hreflang="ar" href="'.url('/platforms').'?lang=ar"', $html);
        $this->assertStringContainsString('hreflang="en" href="'.url('/platforms').'"', $html);
    }

    public function test_arabic_page_is_its_own_canonical_so_google_can_index_it(): void
    {
        $html = $this->get('/platforms?lang=ar')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.url('/platforms').'?lang=ar">', $html);
    }

    public function test_react_pages_follow_the_same_language_rule(): void
    {
        $html = $this->get('/start-project?lang=ar')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.url('/start-project').'?lang=ar">', $html);
        $this->assertStringContainsString('hreflang="en" href="'.url('/start-project').'"', $html);
    }

    public function test_sitemap_english_alternate_is_the_plain_address(): void
    {
        $xml = $this->sitemapXml();

        $this->assertStringContainsString('hreflang="en" href="'.url('/platforms').'"', $xml);
        $this->assertStringContainsString('hreflang="ar" href="'.url('/platforms').'?lang=ar"', $xml);
        $this->assertStringNotContainsString('lang=en', $xml);
        $this->assertStringNotContainsString('catalog?category=', $xml);
    }
}
