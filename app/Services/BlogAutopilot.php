<?php

namespace App\Services;

use App\Models\BlogArticle;
use App\Services\AI\BlogAiService;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\SoftwareStore\Models\SoftwareProduct;

/**
 * Writes and publishes one blog article a run, Arabic and English in turn. The written topics in
 * config/blog_topics.php go first (each in both languages, linked as translations of each other); after them
 * the writer picks its own topic from the angles in config/blog_angles.php. An article that fails the quality
 * check is dropped, never published.
 */
class BlogAutopilot
{
    public const GROUP_PREFIX = 'auto-';

    private const ANGLE_PREFIX = 'auto-angle-';

    private const MIN_WORDS = 600;

    private const ATTEMPTS = 2;

    private const RECENT_TITLES = 40;

    private const AI_PAGE = '/ai';

    /** Opening lines that read like filler, in the languages we write. A paragraph with one of them is removed. */
    private const FILLER = [
        "in today's fast-paced world", 'in the digital age', 'in the ever-evolving', 'in conclusion,', 'delve into', 'game-changer',
        'في عالم اليوم سريع الخطى', 'في العصر الرقمي', 'في عالمنا المتسارع', 'في الختام،', 'لا شك أن', 'ثورة حقيقية',
    ];

    private const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'title' => ['type' => 'string'],
            'slug' => ['type' => 'string'],
            'excerpt' => ['type' => 'string'],
            'content' => ['type' => 'string'],
            'meta_title' => ['type' => 'string'],
            'meta_description' => ['type' => 'string'],
        ],
        'required' => ['title', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description'],
        'additionalProperties' => false,
    ];

    public function __construct(private readonly BlogAiService $ai) {}

    /** @return BlogArticle|null the published article, or null when nothing passed the quality check */
    public function publishNext(): ?BlogArticle
    {
        $language = $this->nextLanguage();
        $brief = $this->nextTopic($language) ?? $this->nextAngle($language);
        if ($brief === null) {
            Log::warning('Blog autopilot: there are no topics and no angles to write about.');

            return null;
        }

        for ($attempt = 1; $attempt <= self::ATTEMPTS; $attempt++) {
            $raw = $this->ai->generate($this->prompt($brief, $language), self::SCHEMA);
            $article = is_array($raw) ? $this->passGate($raw) : null;
            if ($article !== null) {
                return $this->publish($article, $brief, $language);
            }
            Log::info('Blog autopilot: an article failed the quality check.', ['group' => $brief['group'], 'language' => $language, 'attempt' => $attempt]);
        }

        return null;
    }

    /** Arabic and English in turn, starting with Arabic. */
    public function nextLanguage(): string
    {
        $last = BlogArticle::withTrashed()->where('group_id', 'like', self::GROUP_PREFIX.'%')->latest('id')->value('language');

        return $last === 'ar' ? 'en' : 'ar';
    }

    /**
     * The next written topic not yet published in this language. A topic whose product page is hidden is
     * skipped, so it never blocks the topics after it.
     *
     * @return array{group: string, slug: ?string, subject: string, link: string}|null
     */
    private function nextTopic(string $language): ?array
    {
        $liveProducts = $this->liveProducts()->pluck('slug')->all();
        foreach ((array) config('blog_topics', []) as $topic) {
            $group = self::GROUP_PREFIX.$topic['key'];
            $used = BlogArticle::withTrashed()->where('group_id', $group)->where('language', $language)->exists();
            if ($used || ! in_array($topic['product'], $liveProducts, true)) {
                continue;
            }

            return [
                'group' => $group,
                'slug' => $language === 'ar' ? $topic['key'] : $topic['key'].'-en',
                'subject' => 'Topic: '.$topic[$language],
                'link' => '/services/'.$topic['product'],
            ];
        }

        return null;
    }

    /** @return array{group: string, slug: ?string, subject: string, link: string}|null the angles in turn; the writer chooses the topic */
    private function nextAngle(string $language): ?array
    {
        $angles = array_values((array) config('blog_angles', []));
        if ($angles === []) {
            return null;
        }

        $written = BlogArticle::withTrashed()->where('group_id', 'like', self::ANGLE_PREFIX.'%')->where('language', $language)->count();
        $angle = $angles[$written % count($angles)];

        return [
            'group' => self::ANGLE_PREFIX.$angle['key'].'-'.Str::lower(Str::random(10)),
            'slug' => null,
            'subject' => "Choose the topic yourself, a new one that is not in the titles below.\nAngle: ".$angle['brief'],
            'link' => $angle['link'],
        ];
    }

    private function prompt(array $brief, string $language): string
    {
        $languageRule = $language === 'ar'
            ? 'Write in simple Arabic that a shop owner in Egypt or the Gulf understands. No stiff formal Arabic.'
            : 'Write in plain, simple English for a small business owner in the Gulf, Egypt or anywhere else.';

        return <<<PROMPT
You write for Musoftware, a small software studio in Suez, Egypt that builds software for businesses. Write one helpful blog article for a business owner who is deciding what to do.

{$brief['subject']}

Our pages you may link to (only these; never invent a page):
{$this->ourPages()}

Titles already published in this language (do not repeat one or write a near copy):
{$this->recentTitles($language)}

Rules:
- {$languageRule}
- 700 to 1000 words. Use HTML only: h2, h3, p, ul, li, strong, a. No markdown, no h1.
- Be specific and practical: checklists, questions to ask, mistakes to avoid. Explain trade-offs honestly, including when our offer is NOT the right choice.
- Link at least once to {$brief['link']} exactly like <a href="{$brief['link']}">a natural name</a>, inside a normal paragraph, never as an advertisement block.
- Do NOT invent statistics, customer stories, reviews, awards, guarantees, prices or legal deadlines. If you are unsure of a fact, leave it out.
- No emojis. No filler opening such as "in today's fast-paced world". Start with the reader's actual problem.
- End with a short FAQ of 3 questions as h3 plus p.
- slug: 3 to 7 lowercase English words joined by hyphens that describe the topic (also for an Arabic article).
- meta_title under 60 characters; meta_description 140 to 155 characters; excerpt 1 or 2 sentences.

Return ONLY valid JSON: {"title": "...", "slug": "...", "excerpt": "...", "content": "<h2>...</h2><p>...</p>", "meta_title": "...", "meta_description": "..."}
PROMPT;
    }

    private function ourPages(): string
    {
        $lines = ['- '.self::AI_PAGE.': the customer describes what they need in a chat (Arabic or English), can send links, pictures, PDF, Excel and Word files, gets a fixed price before paying, then sees pictures of the work while the website, system or Android app is built.'];
        foreach ($this->liveProducts() as $product) {
            $lines[] = "- /services/{$product->slug}: {$product->name_en}".($product->tagline_en ? " ({$product->tagline_en})" : '');
        }

        return implode("\n", $lines);
    }

    /** Products whose pages visitors can open. */
    private function liveProducts(): Collection
    {
        return SoftwareProduct::where('is_active', true)->orderBy('sort_order')->get(['slug', 'name_en', 'tagline_en']);
    }

    private function recentTitles(string $language): string
    {
        $titles = BlogArticle::where('language', $language)->latest('id')->limit(self::RECENT_TITLES)->pluck('title');

        return $titles->isEmpty() ? 'None yet.' : '- '.$titles->implode("\n- ");
    }

    /** @return array<string, string>|null the cleaned article, or null when it must be dropped */
    private function passGate(array $raw): ?array
    {
        $title = $this->withoutEmoji(trim(strip_tags((string) ($raw['title'] ?? ''))));
        $content = $this->withoutFiller($this->withoutEmoji(HtmlSanitizer::clean((string) ($raw['content'] ?? ''))));

        if ($title === '' || $this->wordCount($content) < self::MIN_WORDS) {
            return null;
        }
        if (! $this->linksOnlyToRealPages($content) || $this->titleExists($title)) {
            return null;
        }

        return [
            'title' => $title,
            'slug' => Str::slug((string) ($raw['slug'] ?? '')),
            'excerpt' => $this->withoutEmoji(trim(strip_tags((string) ($raw['excerpt'] ?? '')))),
            'meta_title' => mb_substr($this->withoutEmoji(trim(strip_tags((string) ($raw['meta_title'] ?? $title)))), 0, 60),
            'meta_description' => mb_substr($this->withoutEmoji(trim(strip_tags((string) ($raw['meta_description'] ?? '')))), 0, 160),
            'content' => $content,
        ];
    }

    private function publish(array $article, array $brief, string $language): BlogArticle
    {
        $slug = $this->uniqueSlug($brief['slug'] ?? ($article['slug'] ?: 'article-'.now()->format('Ymd')));
        unset($article['slug']);

        // a normal save: publishing tells IndexNow and refreshes the AI files, as for an article written by hand
        return BlogArticle::create($article + [
            'language' => $language,
            'group_id' => $brief['group'],
            'slug' => $slug,
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        for ($count = 1; BlogArticle::withTrashed()->where('slug', $slug)->exists(); $count++) {
            $slug = $base.'-'.$count;
        }

        return $slug;
    }

    /** At least one link to our AI page or a live product, and no link to a product page visitors cannot open. */
    private function linksOnlyToRealPages(string $html): bool
    {
        preg_match_all('~href="/services/([a-z0-9-]+)"~', $html, $found);
        $products = array_unique($found[1]);
        $real = $products === [] ? 0 : SoftwareProduct::where('is_active', true)->whereIn('slug', $products)->count();

        if ($real !== count($products)) {
            return false;
        }

        return $real > 0 || str_contains($html, 'href="'.self::AI_PAGE.'"');
    }

    private function titleExists(string $title): bool
    {
        return BlogArticle::withTrashed()->where('title', $title)->exists();
    }

    private function wordCount(string $html): int
    {
        $text = trim(strip_tags($html));

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);
    }

    private function withoutEmoji(string $text): string
    {
        return trim((string) preg_replace('/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $text));
    }

    /** Removes whole paragraphs that contain a filler phrase. */
    private function withoutFiller(string $html): string
    {
        foreach (self::FILLER as $phrase) {
            $html = (string) preg_replace('~<p>[^<]*'.preg_quote($phrase, '~').'[^<]*</p>~iu', '', $html);
        }

        return $html;
    }
}
