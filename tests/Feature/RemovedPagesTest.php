<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Marketplace\Models\Service;
use Tests\TestCase;

/**
 * Old addresses that still get visits: /public/... duplicates of every page, and blog articles that
 * were taken off the blog when it was curated.
 */
class RemovedPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_prefixed_addresses_redirect_to_the_clean_url(): void
    {
        $this->get('/public/dashboard')->assertStatus(301)->assertRedirect(url('/dashboard'));
        $this->get('/public/blog?lang=ar')->assertStatus(301)->assertRedirect(url('/blog?lang=ar'));
        $this->get('/public/index.php/services')->assertStatus(301)->assertRedirect(url('/services'));
        $this->get('/public')->assertStatus(301)->assertHeader('Location', url('/').'/');
        $this->get('/public/')->assertStatus(301)->assertHeader('Location', url('/').'/');
    }

    public function test_similar_paths_and_posts_are_left_alone(): void
    {
        $this->get('/publications')->assertNotFound();
        // not redirected: a redirect would turn a webhook POST into a GET
        $this->post('/public/tools/lead-capture')->assertNotFound();
    }

    private function article(array $attributes): BlogArticle
    {
        return BlogArticle::create($attributes + [
            'language' => 'nl',
            'title' => 'Telegram Media Downloader',
            'content' => '<p>Oud artikel.</p>',
            'excerpt' => 'Oud.',
            'is_published' => true,
            'published_at' => now()->subMonth(),
        ]);
    }

    public function test_removed_article_about_a_listed_service_redirects_to_the_service(): void
    {
        $service = Service::create([
            'seller_id' => User::factory()->create()->id,
            'title' => 'Telegram Media Downloader',
            'slug' => 'telegram-media-downloader',
            'status' => 'active',
            'description' => 'Download media.',
        ]);
        $this->article(['slug' => 'telegram-media-downloader-een-dagelijks-gebruik', 'service_id' => $service->id])->delete();

        $this->get('/blog/telegram-media-downloader-een-dagelijks-gebruik?lang=ar')
            ->assertStatus(301)
            ->assertRedirect(route('marketplace.services.show', ['id' => $service->id, 'slug' => 'telegram-media-downloader']));
    }

    public function test_removed_article_without_a_listed_service_is_gone(): void
    {
        $this->article(['slug' => 'unpublished-old-article', 'is_published' => false]);
        $this->article(['slug' => 'deleted-old-article'])->delete();

        $this->get('/blog/unpublished-old-article')->assertStatus(410)->assertSee('410');
        $this->get('/blog/deleted-old-article?lang=ar')->assertStatus(410)->assertSee('تم حذف هذه الصفحة');
    }

    public function test_unknown_articles_and_drafts_are_still_not_found(): void
    {
        $this->article(['slug' => 'draft', 'is_published' => false, 'published_at' => null]);
        $this->article(['slug' => 'scheduled', 'published_at' => now()->addWeek()]);

        $this->get('/blog/never-existed')->assertNotFound();
        $this->get('/blog/draft')->assertNotFound();
        $this->get('/blog/scheduled')->assertNotFound();
    }
}
