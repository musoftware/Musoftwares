<?php

namespace App\Services;

use App\Models\BlogArticle;
use App\Services\AI\BlogAiService;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\Log;
use Modules\SoftwareStore\Models\SoftwareProduct;

/**
 * Writes one weekly blog DRAFT (Arabic first, then the English version in the same group).
 * Nothing is ever published here: a person reads the draft and publishes it. A draft that fails
 * the quality gate is dropped, never saved.
 */
class BlogDraftService
{
    private const MIN_WORDS = 600;

    private const ATTEMPTS = 2;

    /** Opening lines that read like filler, in the languages we write. A paragraph with one of them is removed. */
    private const FILLER = [
        "in today's fast-paced world", 'in the digital age', 'in the ever-evolving', 'in conclusion,', 'delve into', 'game-changer',
        'في عالم اليوم سريع الخطى', 'في العصر الرقمي', 'في عالمنا المتسارع', 'في الختام،', 'لا شك أن', 'ثورة حقيقية',
    ];

    public function __construct(private readonly BlogAiService $ai) {}

    /** @return array{topic: array<string, string>, articles: array<int, BlogArticle>}|null null when every topic is already used */
    public function createWeeklyDraft(): ?array
    {
        $topic = $this->nextTopic();
        if ($topic === null) {
            Log::info('Blog drafts: every topic in config/blog_topics.php has been used.');

            return null;
        }

        $arabic = $this->draftFor($topic, 'ar');
        if ($arabic === null) {
            return ['topic' => $topic, 'articles' => []];
        }

        $articles = [$arabic];
        $english = $this->draftFor($topic, 'en');
        if ($english === null) {
            Log::warning('Blog drafts: the English version failed the quality gate; only the Arabic draft was saved.', ['topic' => $topic['key']]);
        } else {
            $articles[] = $english;
        }

        return ['topic' => $topic, 'articles' => $articles];
    }

    /** @return array<string, string>|null */
    public function nextTopic(): ?array
    {
        foreach ((array) config('blog_topics', []) as $topic) {
            if (! BlogArticle::withTrashed()->where('group_id', $this->groupId($topic))->exists()) {
                return $topic;
            }
        }

        return null;
    }

    private function groupId(array $topic): string
    {
        return 'draft-'.$topic['key'];
    }

    private function draftFor(array $topic, string $language): ?BlogArticle
    {
        for ($attempt = 1; $attempt <= self::ATTEMPTS; $attempt++) {
            $raw = $this->ai->generate($this->prompt($topic, $language));
            $draft = is_array($raw) ? $this->passGate($raw, $topic, $language) : null;

            if ($draft !== null) {
                return $this->save($draft, $topic, $language);
            }
            Log::info('Blog drafts: a draft failed the quality gate.', ['topic' => $topic['key'], 'language' => $language, 'attempt' => $attempt]);
        }

        return null;
    }

    private function prompt(array $topic, string $language): string
    {
        $product = SoftwareProduct::where('slug', $topic['product'])->first();
        $facts = $product
            ? "Product we may mention: {$product->name_en} / {$product->name_ar}. One line: {$product->tagline_en}. Link: /services/{$product->slug}"
            : "Product page we must link to: /services/{$topic['product']}";
        $subject = $language === 'ar' ? $topic['ar'] : $topic['en'];
        $languageRule = $language === 'ar'
            ? 'Write in simple Arabic that a shop owner in Egypt or the Gulf understands. No stiff formal Arabic.'
            : 'Write in plain, simple English for a small business owner.';

        return <<<PROMPT
You write for Musoftware, a small software studio in Suez, Egypt. Write one helpful blog article for a business owner who is deciding what to buy.

Topic: {$subject}
{$facts}

Rules:
- {$languageRule}
- 700 to 1000 words. Use HTML only: h2, h3, p, ul, li, strong, a. No markdown, no h1.
- Be specific and practical: checklists, questions to ask a seller, mistakes to avoid. Explain trade-offs honestly, including when our product is NOT the right choice.
- Include at least one link to the product page exactly like <a href="/services/{$topic['product']}">product name</a>, written naturally inside a paragraph, never as an advertisement block.
- Do NOT invent statistics, customer stories, reviews, awards, guarantees, prices or legal deadlines. If you are unsure of a fact, leave it out.
- No emojis. No filler opening such as "in today's fast-paced world". Start with the reader's actual problem.
- End with a short FAQ of 3 questions as h3 plus p.

Return ONLY valid JSON: {"title": "...", "excerpt": "1-2 sentences", "content": "<h2>...</h2><p>...</p>", "meta_title": "under 60 characters", "meta_description": "140 to 155 characters"}
PROMPT;
    }

    /** @return array<string, string>|null the cleaned draft, or null when it must be dropped */
    private function passGate(array $raw, array $topic, string $language): ?array
    {
        $title = trim(strip_tags((string) ($raw['title'] ?? '')));
        $content = $this->withoutFiller($this->withoutEmoji(HtmlSanitizer::clean((string) ($raw['content'] ?? ''))));

        if ($title === '' || $this->wordCount($content) < self::MIN_WORDS) {
            return null;
        }
        if (! $this->linksToRealProduct($content) || $this->titleExists($title)) {
            return null;
        }

        return [
            'title' => $this->withoutEmoji($title),
            'excerpt' => $this->withoutEmoji(trim(strip_tags((string) ($raw['excerpt'] ?? '')))),
            'meta_title' => mb_substr($this->withoutEmoji(trim(strip_tags((string) ($raw['meta_title'] ?? $title)))), 0, 60),
            'meta_description' => mb_substr($this->withoutEmoji(trim(strip_tags((string) ($raw['meta_description'] ?? '')))), 0, 160),
            'content' => $content,
        ];
    }

    private function save(array $draft, array $topic, string $language): BlogArticle
    {
        $slug = $language === 'ar' ? $topic['key'] : $topic['key'].'-en';
        $count = 1;
        while (BlogArticle::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $topic['key'].($language === 'ar' ? '' : '-en').'-'.$count++;
        }

        $article = new BlogArticle($draft + [
            'language' => $language,
            'group_id' => $this->groupId($topic),
            'slug' => $slug,
            'is_published' => false,
        ]);
        // quiet: a draft must not queue the AI-files job or tell search engines about itself
        $article->saveQuietly();

        return $article;
    }

    private function linksToRealProduct(string $html): bool
    {
        if (! preg_match_all('~href="/services/([a-z0-9-]+)"~', $html, $found)) {
            return false;
        }

        return SoftwareProduct::whereIn('slug', array_unique($found[1]))->exists();
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
