<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogSeoTest extends TestCase
{
    use RefreshDatabase;

    private function article(array $attributes = []): BlogArticle
    {
        return BlogArticle::create($attributes + [
            'language' => 'ar',
            'group_id' => 'g-1',
            'title' => 'كم تكلفة تصميم موقع شركة',
            'slug' => 'website-cost',
            'content' => '<h2>العنوان</h2><p>'.str_repeat('كلمة ', 400).'</p>',
            'excerpt' => 'ملخص قصير.',
            'meta_title' => 'تكلفة موقع شركة',
            'meta_description' => 'وصف المقال بالتفصيل.',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
    }

    public function test_article_page_has_article_schema_breadcrumb_canonical_and_translations(): void
    {
        $this->article();
        $this->article(['language' => 'en', 'title' => 'Website cost', 'slug' => 'website-cost-en']);

        $html = $this->get('/blog/website-cost?lang=ar')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.url('/blog/website-cost').'"', $html);
        $this->assertStringContainsString('hreflang="en" href="'.url('/blog/website-cost-en').'"', $html);
        $this->assertStringContainsString('hreflang="ar" href="'.url('/blog/website-cost').'"', $html);
        $this->assertStringContainsString('"@type":"Article"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
        $this->assertStringContainsString('"@type":"Organization"', $html);
        $this->assertStringNotContainsString('"@type": "SoftwareApplication"', $html);
        $this->assertSame(1, preg_match_all('/<h1[\s>]/', $html));
    }

    public function test_the_blog_pages_speak_the_language_of_the_visitor(): void
    {
        $this->article();

        $arabic = $this->get('/blog?lang=ar')->assertOk()->getContent();
        $this->assertStringContainsString('اقرا المقال', $arabic);
        $this->assertStringNotContainsString('Technical Library', $arabic);

        $this->get('/blog/website-cost?lang=ar')->assertOk()->assertSee('دقيقة قراءة');
    }

    public function test_search_and_paged_blog_lists_are_not_indexed(): void
    {
        $this->article();

        $this->get('/blog')->assertOk()->assertSee('index,follow', false);
        $this->get('/blog?search=موقع')->assertOk()->assertSee('noindex,follow', false);
    }

    public function test_unpublished_articles_are_not_reachable_or_in_the_sitemap(): void
    {
        $this->article(['slug' => 'hidden', 'is_published' => false]);

        $this->get('/blog/hidden')->assertNotFound();
        $this->assertStringNotContainsString('/blog/hidden', $this->get('/sitemap.xml')->getContent());
    }
}
