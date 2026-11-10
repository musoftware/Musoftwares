<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_fixed_page_in_the_sitemap_opens_without_a_redirect(): void
    {
        $xml = $this->sitemapXml();
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
        $xml = $this->sitemapXml();

        $this->assertStringContainsString('<loc>'.url('/estimator').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/blog').'</loc>', $xml);
        $this->assertStringNotContainsString('<loc>'.url('/pricing').'</loc>', $xml);
    }

    public function test_sitemap_is_an_index_of_small_parts(): void
    {
        \App\Models\BlogArticle::create(['language' => 'en', 'title' => 'One', 'slug' => 'one', 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now()->subDay()]);

        $index = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->getContent();
        $this->assertStringContainsString('<sitemapindex', $index);
        foreach (['pages', 'marketplace', 'blog-en-1'] as $part) {
            $this->assertStringContainsString('<loc>'.url("/sitemaps/{$part}.xml").'</loc>', $index);
            $xml = $this->get("/sitemaps/{$part}.xml")->assertOk()->getContent();
            $this->assertNotFalse(simplexml_load_string($xml), "{$part} is not valid XML");
        }

        $this->assertStringContainsString(url('/blog/one'), $this->get('/sitemaps/blog-en-1.xml')->getContent());
        $this->assertStringNotContainsString('blog-en-2', $index);
        $this->get('/sitemaps/blog-en-2.xml')->assertNotFound();
        $this->get('/sitemaps/nope.xml')->assertNotFound();
    }

    public function test_arabic_and_english_articles_have_their_own_sitemap_parts(): void
    {
        $article = fn (string $language, string $slug, ?string $group = null) => \App\Models\BlogArticle::create([
            'language' => $language, 'group_id' => $group, 'title' => $slug, 'slug' => $slug, 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now()->subDay(),
        ]);
        $article('ar', 'arabic-one', 'pair');
        $article('en', 'english-one', 'pair');
        $article('en', 'english-two');

        $index = $this->get('/sitemap.xml')->getContent();
        $this->assertStringContainsString(url('/sitemaps/blog-ar-1.xml'), $index);
        $this->assertStringContainsString(url('/sitemaps/blog-en-1.xml'), $index);

        $arabic = $this->get('/sitemaps/blog-ar-1.xml')->assertOk()->getContent();
        $english = $this->get('/sitemaps/blog-en-1.xml')->assertOk()->getContent();
        $this->assertSame(1, substr_count($arabic, '<loc>'));
        $this->assertStringContainsString('<loc>'.url('/blog/arabic-one').'</loc>', $arabic);
        $this->assertSame(2, substr_count($english, '<loc>'));
        $this->assertStringNotContainsString('<loc>'.url('/blog/arabic-one').'</loc>', $english);
        // the translation pair still points at each other across the two parts
        $this->assertStringContainsString(url('/blog/english-one'), $arabic);
    }
}
