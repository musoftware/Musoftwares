<?php

namespace Tests\Feature;

use App\Jobs\SubmitIndexNowJob;
use App\Models\AdminSettings;
use App\Models\BlogArticle;
use App\Services\IndexNowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Tests\TestCase;

class IndexNowTest extends TestCase
{
    use RefreshDatabase;

    private function publishedArticle(string $slug = 'a-guide'): BlogArticle
    {
        return BlogArticle::withoutEvents(fn () => BlogArticle::create([
            'language' => 'ar', 'title' => 'مقال', 'slug' => $slug, 'content' => '<p>نص</p>', 'excerpt' => 'ملخص',
            'is_published' => true, 'published_at' => now()->subDay(),
        ]));
    }

    private function activateOneProduct(): SoftwareProduct
    {
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::query()->update(['is_active' => false]);
        SoftwareProduct::where('slug', 'pharmacy-system')->update(['is_active' => true]);

        return SoftwareProduct::where('slug', 'pharmacy-system')->firstOrFail();
    }

    public function test_the_key_is_made_once_kept_and_served_as_a_text_file(): void
    {
        $service = app(IndexNowService::class);
        $key = $service->key();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $key);
        $this->assertSame($key, $service->key());
        $this->assertSame($key, AdminSettings::GetValue('indexnow_key'));

        $this->get('/'.$key.'.txt')->assertOk()->assertSee($key, false)->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->get('/'.str_repeat('a', 32).'.txt')->assertNotFound();
    }

    public function test_it_sends_the_real_payload_and_never_sends_an_unchanged_page_twice(): void
    {
        Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);
        $this->activateOneProduct();
        $this->publishedArticle();
        $service = app(IndexNowService::class);

        $first = $service->submitChanged();
        $this->assertGreaterThanOrEqual(5, $first); // home, services, blog, one product, one article

        Http::assertSent(function ($request) use ($service) {
            $body = $request->data();

            return $request->url() === 'https://api.indexnow.org/indexnow'
                && $body['key'] === $service->key()
                && $body['keyLocation'] === url('/'.$service->key().'.txt')
                && $body['host'] === parse_url(url('/'), PHP_URL_HOST)
                && in_array(route('blog.show', 'a-guide'), $body['urlList'], true)
                && in_array(route('website-services.show', 'pharmacy-system'), $body['urlList'], true);
        });

        $this->assertSame(0, $service->submitChanged());
        Http::assertSentCount(1);
    }

    public function test_only_the_changed_page_is_sent_again(): void
    {
        Http::fake(['api.indexnow.org/*' => Http::response('', 202)]);
        $product = $this->activateOneProduct();
        $service = app(IndexNowService::class);
        $service->submitChanged();

        $this->travel(2)->minutes();
        SoftwareProduct::withoutEvents(fn () => $product->update(['tagline_ar' => 'نص جديد']));

        $changed = $service->changedUrls();

        $this->assertContains(route('website-services.show', 'pharmacy-system'), $changed);
        $this->assertNotContains(route('blog.index'), $changed);
    }

    public function test_a_rejected_submit_is_not_remembered_so_it_is_tried_again(): void
    {
        Http::fake(['api.indexnow.org/*' => Http::response('forbidden', 403)]);
        $this->publishedArticle();
        $service = app(IndexNowService::class);

        $this->assertSame(0, $service->submitChanged());
        $this->assertContains(route('blog.show', 'a-guide'), $service->changedUrls());
    }

    public function test_the_command_is_quiet_when_off_and_force_sends_everything(): void
    {
        Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);
        $this->publishedArticle();
        AdminSettings::SetValue('indexnow_enabled', '0');

        $this->assertSame(0, Artisan::call('seo:indexnow'));
        $this->assertStringContainsString('IndexNow is off', Artisan::output());
        Http::assertNothingSent();

        $this->assertSame(0, Artisan::call('seo:indexnow', ['--force' => true]));
        $this->assertStringContainsString('accepted', Artisan::output());
        Http::assertSentCount(1);
    }

    public function test_saving_a_product_queues_one_run(): void
    {
        $product = $this->activateOneProduct();

        Bus::fake();
        Cache::flush(); // the job is unique, so an earlier dispatch would hold the lock
        $product->update(['tagline_ar' => 'تعديل']);

        Bus::assertDispatched(SubmitIndexNowJob::class);
    }

    public function test_publishing_an_article_queues_one_run_and_a_draft_does_not(): void
    {
        Bus::fake();
        Cache::flush();
        BlogArticle::create(['language' => 'ar', 'title' => 'مسودة', 'slug' => 'draft-x', 'content' => '<p>x</p>', 'is_published' => false]);
        Bus::assertNotDispatched(SubmitIndexNowJob::class);

        BlogArticle::create(['language' => 'ar', 'title' => 'منشور', 'slug' => 'live-x', 'content' => '<p>x</p>', 'is_published' => true]);
        Bus::assertDispatched(SubmitIndexNowJob::class);
    }
}
