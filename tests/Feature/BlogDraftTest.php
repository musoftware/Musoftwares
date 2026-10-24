<?php

namespace Tests\Feature;

use App\Jobs\SubmitIndexNowJob;
use App\Mail\BlogDraftReadyMail;
use App\Models\AdminSettings;
use App\Models\BlogArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Tests\TestCase;

class BlogDraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SoftwareProductSeeder::class);
        AdminSettings::SetValue('gemini_api_key', 'test-key');
        config(['blog_topics' => [
            ['key' => 'topic-one', 'product' => 'dental-clinic-system', 'ar' => 'موضوع أول', 'en' => 'First topic'],
            ['key' => 'topic-two', 'product' => 'pharmacy-system', 'ar' => 'موضوع تاني', 'en' => 'Second topic'],
        ]]);
    }

    /** A model answer in the shape Gemini returns: the JSON article sits inside a text part. */
    private function modelAnswer(array $article)
    {
        return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($article, JSON_UNESCAPED_UNICODE)]]]]]], 200);
    }

    private function article(string $title, int $words = 650, string $link = '/services/dental-clinic-system', string $extra = ''): array
    {
        $body = trim(str_repeat('كلمة عملية ', $words));

        return [
            'title' => $title,
            'excerpt' => 'ملخص قصير للمقال.',
            'content' => "<h2>عنوان</h2><p>{$body} <a href=\"{$link}\">المنتج</a></p>{$extra}<h3>سؤال؟</h3><p>جواب.</p>",
            'meta_title' => 'عنوان ميتا',
            'meta_description' => 'وصف ميتا للمقال.',
        ];
    }

    private function fakeLlm(array ...$articles): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence(array_map(fn (array $a) => $this->modelAnswer($a), $articles))]);
    }

    public function test_the_command_does_nothing_while_drafts_are_off(): void
    {
        Http::fake();

        $this->assertSame(0, Artisan::call('blog:draft-weekly'));

        $this->assertStringContainsString('are off', Artisan::output());
        Http::assertNothingSent();
        $this->assertSame(0, BlogArticle::count());
    }

    public function test_it_saves_an_arabic_and_an_english_draft_that_are_never_published(): void
    {
        AdminSettings::SetValue('blog_drafts_enabled', '1');
        Mail::fake();
        Bus::fake();
        $this->fakeLlm($this->article('عنوان عربي'), $this->article('English title'));

        $this->assertSame(0, Artisan::call('blog:draft-weekly'));

        $drafts = BlogArticle::orderBy('id')->get();
        $this->assertCount(2, $drafts);
        $this->assertSame(['ar', 'en'], $drafts->pluck('language')->all());
        $this->assertSame(['draft-topic-one'], $drafts->pluck('group_id')->unique()->all());
        $this->assertSame(['topic-one', 'topic-one-en'], $drafts->pluck('slug')->all());
        $this->assertTrue($drafts->every(fn (BlogArticle $a) => $a->is_published === false && $a->published_at === null));

        Mail::assertSent(BlogDraftReadyMail::class, fn (BlogDraftReadyMail $mail) => $mail->articles->count() === 2 && str_contains($mail->render(), 'ما اتنشرتش'));
        Bus::assertNotDispatched(SubmitIndexNowJob::class); // a draft does not tell search engines anything
    }

    public function test_the_next_week_uses_the_next_topic_and_no_topic_repeats(): void
    {
        AdminSettings::SetValue('blog_drafts_enabled', '1');
        Mail::fake();
        $this->fakeLlm($this->article('عنوان 1'), $this->article('Title 1'), $this->article('عنوان 2', link: '/services/pharmacy-system'), $this->article('Title 2', link: '/services/pharmacy-system'));

        Artisan::call('blog:draft-weekly');
        Artisan::call('blog:draft-weekly');
        $this->assertSame(['draft-topic-one', 'draft-topic-two'], BlogArticle::pluck('group_id')->unique()->values()->all());

        Http::fake();
        Artisan::call('blog:draft-weekly');
        $this->assertStringContainsString('already used', Artisan::output());
        $this->assertSame(4, BlogArticle::count());
    }

    public function test_a_short_draft_or_one_without_a_real_product_link_is_dropped_after_two_tries(): void
    {
        AdminSettings::SetValue('blog_drafts_enabled', '1');
        Mail::fake();
        $this->fakeLlm($this->article('قصير', words: 100), $this->article('بدون رابط', link: '/services/not-a-real-page'));

        $this->assertSame(1, Artisan::call('blog:draft-weekly'));

        $this->assertSame(0, BlogArticle::count());
        Mail::assertNothingSent();
    }

    public function test_a_duplicate_title_is_rejected_and_the_retry_can_pass(): void
    {
        AdminSettings::SetValue('blog_drafts_enabled', '1');
        Mail::fake();
        BlogArticle::create(['language' => 'ar', 'title' => 'عنوان مكرر', 'slug' => 'old', 'content' => '<p>x</p>', 'is_published' => false]);
        $this->fakeLlm($this->article('عنوان مكرر'), $this->article('عنوان جديد'), $this->article('New English title'));

        Artisan::call('blog:draft-weekly');

        $this->assertTrue(BlogArticle::where('title', 'عنوان جديد')->exists());
        $this->assertSame(1, BlogArticle::where('title', 'عنوان مكرر')->count());
    }

    public function test_emoji_and_filler_paragraphs_are_stripped(): void
    {
        AdminSettings::SetValue('blog_drafts_enabled', '1');
        Mail::fake();
        $this->fakeLlm(
            $this->article("عنوان \u{1F680} نضيف", extra: '<p>في عالم اليوم سريع الخطى كل حاجة بتتغير.</p><p>فقرة سليمة \u{1F600} عادية.</p>'),
            $this->article('English clean title'),
        );

        Artisan::call('blog:draft-weekly');

        $arabic = BlogArticle::where('language', 'ar')->firstOrFail();
        $this->assertStringNotContainsString("\u{1F680}", $arabic->title);
        $this->assertStringNotContainsString("\u{1F600}", $arabic->content);
        $this->assertStringNotContainsString('في عالم اليوم سريع الخطى', $arabic->content);
        $this->assertStringContainsString('فقرة سليمة', $arabic->content);
    }
}
