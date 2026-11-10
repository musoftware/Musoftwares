<?php

namespace Tests\Feature;

use App\Jobs\SubmitIndexNowJob;
use App\Mail\BlogArticlePublishedMail;
use App\Models\AdminSettings;
use App\Models\BlogArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Tests\TestCase;

class BlogAutopilotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::where('slug', 'dental-clinic-system')->update(['is_active' => true]);
        // the Gemini path, which the tests can fake over HTTP; production writes with Claude when its key is set
        config(['services.anthropic.key' => null]);
        AdminSettings::SetValue('gemini_api_key', 'test-key');
        config(['blog_topics' => [
            ['key' => 'topic-one', 'product' => 'dental-clinic-system', 'ar' => 'موضوع أول', 'en' => 'First topic'],
        ]]);
        config(['blog_angles' => [
            ['key' => 'excel-to-program', 'link' => '/ai', 'brief' => 'Excel brief'],
            ['key' => 'android-app', 'link' => '/ai', 'brief' => 'Android brief'],
        ]]);
        Mail::fake();
    }

    /** A model answer in the shape Gemini returns: the JSON article sits inside a text part. */
    private function modelAnswer(array $article)
    {
        return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($article, JSON_UNESCAPED_UNICODE)]]]]]], 200);
    }

    private function article(string $title, int $words = 650, string $link = '/services/dental-clinic-system', string $extra = '', string $slug = 'some-topic'): array
    {
        $body = trim(str_repeat('كلمة عملية ', $words));

        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => 'ملخص قصير للمقال.',
            'content' => "<h2>عنوان</h2><p>{$body} <a href=\"{$link}\">الصفحة</a></p>{$extra}<h3>سؤال؟</h3><p>جواب.</p>",
            'meta_title' => 'عنوان ميتا',
            'meta_description' => 'وصف ميتا للمقال.',
        ];
    }

    private function fakeLlm(array ...$articles): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence(array_map(fn (array $a) => $this->modelAnswer($a), $articles))]);
    }

    public function test_the_command_does_nothing_while_the_autopilot_is_off(): void
    {
        AdminSettings::SetValue('blog_autopilot_enabled', '0');
        Http::fake();

        $this->assertSame(0, Artisan::call('blog:autopilot'));

        $this->assertStringContainsString('is off', Artisan::output());
        Http::assertNothingSent();
        $this->assertSame(0, BlogArticle::count());
    }

    public function test_it_publishes_one_article_tells_search_engines_and_the_owner(): void
    {
        Bus::fake();
        $this->fakeLlm($this->article('عنوان عربي'));

        $this->assertSame(0, Artisan::call('blog:autopilot'));

        $article = BlogArticle::sole();
        $this->assertSame(['ar', 'auto-topic-one', 'topic-one'], [$article->language, $article->group_id, $article->slug]);
        $this->assertTrue($article->is_published);
        $this->assertNotNull($article->published_at);
        $this->get('/blog/topic-one')->assertOk()->assertSee('عنوان عربي');

        Bus::assertDispatched(SubmitIndexNowJob::class);
        Mail::assertSent(BlogArticlePublishedMail::class, fn (BlogArticlePublishedMail $mail) => str_contains($mail->render(), 'اتنشر') && str_contains($mail->render(), '/blog/topic-one'));
    }

    public function test_languages_take_turns_and_the_written_topic_is_used_once_in_each(): void
    {
        $this->fakeLlm(
            $this->article('عنوان 1'),
            $this->article('Title 1'),
            $this->article('إكسل لبرنامج', link: '/ai', slug: 'excel-sheet-to-program'),
            $this->article('Excel to an app', link: '/ai', slug: 'excel-sheet-to-app'),
        );

        foreach (range(1, 4) as $run) {
            $this->assertSame(0, Artisan::call('blog:autopilot'));
        }

        $articles = BlogArticle::orderBy('id')->get();
        $this->assertSame(['ar', 'en', 'ar', 'en'], $articles->pluck('language')->all());
        $this->assertSame(['topic-one', 'topic-one-en', 'excel-sheet-to-program', 'excel-sheet-to-app'], $articles->pluck('slug')->all());
        // the written topic in both languages is one translation pair; the writer's own topics stand alone
        $this->assertSame($articles[0]->group_id, $articles[1]->group_id);
        $this->assertStringStartsWith('auto-angle-excel-to-program-', $articles[2]->group_id);
        $this->assertNotSame($articles[2]->group_id, $articles[3]->group_id);
    }

    public function test_the_writer_is_told_the_angle_our_pages_and_the_titles_to_avoid(): void
    {
        config(['blog_topics' => []]);
        BlogArticle::create(['language' => 'ar', 'title' => 'مقال قديم منشور', 'slug' => 'old', 'content' => '<p>x</p>', 'is_published' => true]);
        $this->fakeLlm($this->article('مقال جديد', link: '/ai'));

        Artisan::call('blog:autopilot');

        Http::assertSent(function (Request $request) {
            $prompt = $request['contents'][0]['parts'][0]['text'];

            return str_contains($prompt, 'Excel brief')
                && str_contains($prompt, 'مقال قديم منشور')
                && str_contains($prompt, '/services/dental-clinic-system')
                && str_contains($prompt, '- /ai:');
        });
    }

    public function test_a_topic_whose_product_page_is_hidden_is_skipped(): void
    {
        config(['blog_topics' => [
            ['key' => 'hidden-one', 'product' => 'pharmacy-system', 'ar' => 'مخفي', 'en' => 'Hidden'],
            ['key' => 'topic-one', 'product' => 'dental-clinic-system', 'ar' => 'موضوع أول', 'en' => 'First topic'],
        ]]);
        $this->fakeLlm($this->article('عنوان عربي'));

        Artisan::call('blog:autopilot');

        $this->assertSame('auto-topic-one', BlogArticle::sole()->group_id);
    }

    public function test_a_link_to_a_hidden_product_page_is_not_allowed(): void
    {
        $this->fakeLlm($this->article('رابط لمنتج مخفي', link: '/services/pharmacy-system'), $this->article('رابط لمنتج مخفي تاني', link: '/services/pharmacy-system'));

        $this->assertSame(1, Artisan::call('blog:autopilot'));
        $this->assertSame(0, BlogArticle::count());
    }

    public function test_a_short_article_or_one_linking_to_a_made_up_page_is_dropped_after_two_tries(): void
    {
        $this->fakeLlm($this->article('قصير', words: 100), $this->article('رابط وهمي', link: '/services/not-a-real-page'));

        $this->assertSame(1, Artisan::call('blog:autopilot'));

        $this->assertSame(0, BlogArticle::count());
        Mail::assertNothingSent();
    }

    public function test_an_article_without_any_link_to_us_is_dropped(): void
    {
        $this->fakeLlm($this->article('بدون رابط', link: 'https://example.com'), $this->article('بدون رابط تاني', link: 'https://example.com'));

        $this->assertSame(1, Artisan::call('blog:autopilot'));

        $this->assertSame(0, BlogArticle::count());
    }

    public function test_a_duplicate_title_is_rejected_and_the_retry_can_pass(): void
    {
        BlogArticle::create(['language' => 'ar', 'title' => 'عنوان مكرر', 'slug' => 'old', 'content' => '<p>x</p>', 'is_published' => false]);
        $this->fakeLlm($this->article('عنوان مكرر'), $this->article('عنوان جديد'));

        Artisan::call('blog:autopilot');

        $this->assertTrue(BlogArticle::where('title', 'عنوان جديد')->where('is_published', true)->exists());
        $this->assertSame(1, BlogArticle::where('title', 'عنوان مكرر')->count());
    }

    public function test_a_slug_already_taken_gets_a_number(): void
    {
        BlogArticle::create(['language' => 'ar', 'title' => 'قديم', 'slug' => 'topic-one', 'content' => '<p>x</p>', 'is_published' => false]);
        $this->fakeLlm($this->article('عنوان عربي'));

        Artisan::call('blog:autopilot');

        $this->assertSame('topic-one-1', BlogArticle::where('title', 'عنوان عربي')->value('slug'));
    }

    public function test_emoji_and_filler_paragraphs_are_stripped(): void
    {
        $this->fakeLlm($this->article("عنوان \u{1F680} نضيف", extra: '<p>في عالم اليوم سريع الخطى كل حاجة بتتغير.</p><p>فقرة سليمة \u{1F600} عادية.</p>'));

        Artisan::call('blog:autopilot');

        $article = BlogArticle::sole();
        $this->assertStringNotContainsString("\u{1F680}", $article->title);
        $this->assertStringNotContainsString("\u{1F600}", $article->content);
        $this->assertStringNotContainsString('في عالم اليوم سريع الخطى', $article->content);
        $this->assertStringContainsString('فقرة سليمة', $article->content);
    }
}
