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

    public function test_an_arabic_article_opens_for_an_english_visitor_instead_of_redirecting(): void
    {
        $this->article();
        $this->article(['language' => 'en', 'title' => 'Website cost', 'slug' => 'website-cost-en']);

        $html = $this->get('/blog/website-cost?lang=en')->assertOk()->getContent();

        $this->assertStringContainsString('<html lang="ar" dir="rtl"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/blog/website-cost').'"', $html);
    }

    public function test_the_sitemap_links_each_article_to_its_translation_by_its_own_address(): void
    {
        $this->article();
        $this->article(['language' => 'en', 'title' => 'Website cost', 'slug' => 'website-cost-en']);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('hreflang="en" href="'.url('/blog/website-cost-en').'"', $xml);
        $this->assertStringNotContainsString('/blog/website-cost?lang=', $xml);
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

    public function test_a_translation_without_a_slug_does_not_break_the_sitemap_or_the_article(): void
    {
        $this->article(['group_id' => 'g-9', 'slug' => 'with-slug']);
        // generated content: a published translation in the same group whose slug is empty
        $broken = $this->article(['group_id' => 'g-9', 'language' => 'en', 'slug' => 'temp-slug']);
        \Illuminate\Support\Facades\DB::table('blog_articles')->where('id', $broken->id)->update(['slug' => '']);

        $this->get('/sitemap.xml')->assertOk()->assertSee('/blog/with-slug', false);
        $this->get('/blog/with-slug')->assertOk();
    }

    public function test_unpublished_articles_are_not_reachable_or_in_the_sitemap(): void
    {
        $this->article(['slug' => 'hidden', 'is_published' => false]);

        // it was published before, so the address answers 410 Gone (see RemovedPagesTest)
        $this->get('/blog/hidden')->assertStatus(410);
        $this->assertStringNotContainsString('/blog/hidden', $this->get('/sitemap.xml')->getContent());
    }
}
