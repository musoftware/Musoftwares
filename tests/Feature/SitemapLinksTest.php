<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_fixed_page_in_the_sitemap_opens_without_a_redirect(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        preg_match_all('#<loc>([^<]+)</loc>#', $xml, $matches);

        $fixedPages = collect($matches[1])->reject(fn (string $loc) => preg_match('#/(blog|marketplace|services)/#', $loc));

        $this->assertNotEmpty($fixedPages);

        foreach ($fixedPages as $loc) {
            $path = parse_url($loc, PHP_URL_PATH) ?: '/';
            $this->get($path)->assertOk(); // a redirect (301 or 302) fails here, so a moved page cannot stay in the sitemap
        }
    }

    public function test_the_estimator_and_the_blog_are_listed(): void
    {
        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('<loc>'.url('/estimator').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/blog').'</loc>', $xml);
        $this->assertStringNotContainsString('<loc>'.url('/pricing').'</loc>', $xml);
    }
}
