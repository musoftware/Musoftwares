<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Support\HtmlSanitizer;
use Database\Seeders\CuratedBlogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuratedBlogTest extends TestCase
{
    use RefreshDatabase;

    private function seedAndLoad(): \Illuminate\Support\Collection
    {
        $this->seed(CuratedBlogSeeder::class);

        return BlogArticle::whereIn('slug', $this->curatedSlugs())->get();
    }

    private function curatedSlugs(): array
    {
        return array_merge(
            array_column(\Database\Seeders\CuratedBlogContentAr::articles(), 'slug'),
            array_column(\Database\Seeders\CuratedBlogContentEn::articles(), 'slug'),
        );
    }

    private function wordCount(string $html): int
    {
        $text = trim(html_entity_decode(strip_tags($html)));

        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));
    }

    public function test_it_creates_six_arabic_and_three_english_published_articles(): void
    {
        $articles = $this->seedAndLoad();

        $this->assertCount(9, $articles);
        $this->assertSame(6, $articles->where('language', 'ar')->count());
        $this->assertSame(3, $articles->where('language', 'en')->count());
        $this->assertTrue($articles->every(fn ($article) => $article->is_published && $article->service_id === null));
        $this->assertSame(9, BlogArticle::published()->whereIn('slug', $this->curatedSlugs())->count(), 'all dated in the past, so all are visible');
    }

    public function test_english_versions_share_a_group_id_with_their_arabic_article(): void
    {
        $groups = $this->seedAndLoad()->groupBy('group_id');

        $this->assertCount(6, $groups);
        $paired = $groups->filter(fn ($group) => $group->count() === 2);
        $this->assertCount(3, $paired);

        foreach ($paired as $group) {
            $this->assertEqualsCanonicalizing(['ar', 'en'], $group->pluck('language')->all());
        }
    }

    public function test_running_the_seeder_twice_changes_nothing(): void
    {
        $this->seedAndLoad();
        $this->seed(CuratedBlogSeeder::class);

        $this->assertSame(9, BlogArticle::withTrashed()->whereIn('slug', $this->curatedSlugs())->count());
    }

    public function test_a_soft_deleted_article_comes_back_instead_of_being_duplicated(): void
    {
        $this->seedAndLoad()->first()->delete();
        $this->seed(CuratedBlogSeeder::class);

        $this->assertSame(9, BlogArticle::whereIn('slug', $this->curatedSlugs())->count());
        $this->assertSame(9, BlogArticle::withTrashed()->whereIn('slug', $this->curatedSlugs())->count());
    }

    public function test_articles_are_long_enough_and_have_complete_meta_text(): void
    {
        foreach ($this->seedAndLoad() as $article) {
            $minimum = $article->language === 'ar' ? 700 : 600;
            $this->assertGreaterThanOrEqual($minimum, $this->wordCount($article->content), $article->slug.' is too short');
            $this->assertLessThanOrEqual(60, mb_strlen($article->meta_title), $article->slug.' meta title');
            $this->assertGreaterThanOrEqual(140, mb_strlen($article->meta_description), $article->slug.' meta description is short');
            $this->assertLessThanOrEqual(155, mb_strlen($article->meta_description), $article->slug.' meta description is long');
            $this->assertNotEmpty($article->excerpt);
        }
    }

    public function test_no_emoji_and_no_filler_openers(): void
    {
        foreach ($this->seedAndLoad() as $article) {
            $all = $article->title.$article->content.$article->excerpt;
            $this->assertSame(0, preg_match('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B50}\x{FE0F}]/u', $all), $article->slug.' has an emoji');
            $this->assertStringNotContainsString("fast-paced world", $all);
        }
    }

    public function test_internal_links_point_to_real_pages(): void
    {
        $storeSlugs = [];
        foreach (glob(base_path('Modules/SoftwareStore/Database/Seeders/*.php')) as $file) {
            preg_match_all("/'slug' => '([a-z0-9-]+)'/", file_get_contents($file), $found);
            $storeSlugs = array_merge($storeSlugs, $found[1]);
        }
        $blogSlugs = $this->curatedSlugs();
        $fixed = ['/estimator', '/about/mahmoud-amin'];

        foreach ($this->seedAndLoad() as $article) {
            preg_match_all('/href="([^"]+)"/', $article->content, $links);
            foreach ($links[1] as $href) {
                if (str_starts_with($href, '/services/')) {
                    $this->assertContains(substr($href, strlen('/services/')), $storeSlugs, "{$article->slug} links to a missing product: $href");
                } elseif (str_starts_with($href, '/blog/')) {
                    $this->assertContains(substr($href, strlen('/blog/')), $blogSlugs, "{$article->slug} links to a missing article: $href");
                } else {
                    $this->assertContains($href, $fixed, "{$article->slug} has an unexpected link: $href");
                }
            }
        }
    }

    public function test_the_sanitizer_keeps_everything_we_wrote(): void
    {
        foreach ($this->seedAndLoad() as $article) {
            $clean = HtmlSanitizer::clean($article->content);

            $this->assertSame($article->content, $clean, $article->slug.' is changed by the sanitizer');
            $this->assertGreaterThanOrEqual(5, substr_count($clean, '<h2>'), $article->slug.' needs its section headings');
            $this->assertStringContainsString('<a href="/', $clean);
        }
    }

    public function test_arabic_articles_open_on_the_public_blog(): void
    {
        $this->seedAndLoad();

        $this->get('/blog/website-design-cost-egypt-2026?lang=ar')->assertOk()->assertSee('lightweb2.com', false);
        $this->get('/blog/website-design-cost-egypt-2026-en?lang=en')->assertOk();
    }
}
