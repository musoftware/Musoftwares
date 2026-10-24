<?php

namespace Modules\SoftwareStore\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

/**
 * Every store page must be one language: no Arabic text on an English page, and no stray English
 * sentences on an Arabic page. Brand and technology names are allowed in both.
 */
class StoreLanguageAuditTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    /** Words that stay in Latin letters on an Arabic page. */
    private const LATIN_OK = [
        'Musoftware', 'Musoftwares', 'Kashier', 'WhatsApp', 'Windows', 'Mac', 'macOS', 'Laravel', 'PHP', 'MySQL', 'SQLite', 'cPanel', 'Redis',
        'Facebook', 'Google', 'Maps', 'Telegram', 'Excel', 'CSV', 'PDF', 'API', 'ETA', 'SEO', 'POS', 'QR', 'URL', 'USB', 'AI', 'ERP', 'CRM',
        'Salla', 'Zid', 'Shopify', 'WooCommerce', 'WordPress', 'Elementor', 'Meta', 'Instagram', 'TikTok', 'LinkedIn', 'Gmail', 'Outlook',
        'Chrome', 'Edge', 'Android', 'iOS', 'EGP', 'USD', 'SAR', 'AED', 'OMR', 'EN', 'AR', 'SMS', 'FEFO', 'KDS', 'RTL', 'LTR', 'VAT', 'GS1',
        'Flutter', 'React', 'Figma', 'Canva', 'HTML', 'CSS', 'JSON', 'OAuth', 'Cloud', 'SSL', 'DNS', 'MX', 'SMTP', 'IMAP', 'Pro', 'Inbox', 'Sender',
        'Email', 'Finder', 'Leads', 'Shop', 'Manager', 'Systems', 'Smile', 'Dental', 'Center', 'Cairo', 'Grill', 'Care', 'Pharmacy', 'Admin', 'Main', 'Suez',
        // the language switch, the owner's e-mail address, file names and technical terms that are written in Latin letters on purpose
        'English', 'admin', 'musoftwares', 'com', 'txt', 'csv', 'bit', 'AES', 'Spintax', 'spintax', 'SendGrid', 'Cloudflare', 'WebP', 'robots', 'dot', 'info', 'IDs', 'SaaS', 'cron',
        'Analytics', 'Search', 'Console', 'Enter', 'Lead', 'Business', 'Suite', 'Lite', 'Basic', 'Personal', 'Company', 'gmial',
        // order number prefix, e-mail and domain examples, and Google's own term
        'SW-', 'AES-', 'sales', 'offer', 'yourbrand', 'Core', 'Web', 'Vitals',
    ];

    private function seedStore(): void
    {
        $this->seedUsdEgpRates();
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::query()->update(['is_active' => true]);
    }

    /** The visible text of a page: scripts, styles, JSON-LD, comments and attribute values removed. */
    private function visibleText(string $html): string
    {
        $html = preg_replace('~<(script|style|noscript|svg)\b.*?</\1>~is', ' ', $html) ?? $html;
        $html = preg_replace('~<!--.*?-->~s', ' ', $html) ?? $html;

        return trim(preg_replace('~\s+~u', ' ', html_entity_decode(strip_tags($html))) ?? '');
    }

    /** @return array<int, string> */
    private function arabicFragments(string $text): array
    {
        preg_match_all('~[\p{Arabic}][\p{Arabic}\s\d\p{P}]*~u', $text, $matches);

        // the language switch ("العربية") is allowed on an English page
        return array_values(array_filter(array_map('trim', $matches[0]), fn (string $part) => mb_strlen($part) > 1 && ! in_array($part, ['العربية', 'عربي'], true)));
    }

    /** @return array<int, string> */
    private function latinFragments(string $text): array
    {
        preg_match_all('~[A-Za-z][A-Za-z\'-]{2,}~', $text, $matches);

        return array_values(array_unique(array_filter($matches[0], fn (string $word) => ! in_array($word, self::LATIN_OK, true) && ! preg_match('~^[A-Z0-9]{2,}$~', $word))));
    }

    public function test_english_product_pages_have_no_arabic_text(): void
    {
        $this->seedStore();
        $problems = [];

        foreach (SoftwareProduct::pluck('slug') as $slug) {
            $fragments = $this->arabicFragments($this->visibleText($this->get("/services/{$slug}?lang=en")->assertOk()->getContent()));
            if ($fragments !== []) {
                $problems[$slug] = array_slice($fragments, 0, 3);
            }
        }

        $this->assertSame([], $problems, 'Arabic text on English pages: '.json_encode($problems, JSON_UNESCAPED_UNICODE));
    }

    public function test_arabic_product_pages_have_no_stray_english_words(): void
    {
        $this->seedStore();
        $problems = [];

        foreach (SoftwareProduct::pluck('slug') as $slug) {
            $words = $this->latinFragments($this->visibleText($this->get("/services/{$slug}?lang=ar")->assertOk()->getContent()));
            if ($words !== []) {
                $problems[$slug] = array_slice($words, 0, 8);
            }
        }

        $this->assertSame([], $problems, 'English words on Arabic pages: '.json_encode($problems));
    }

    public function test_list_and_result_pages_are_one_language_in_both_languages(): void
    {
        $this->seedStore();
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $this->post('/services/pharmacy-system/order', ['client_name' => 'Sara', 'client_email' => 'sara@example.com', 'client_whatsapp' => '+201000000000', 'plan_key' => 'source']);
        $order = SoftwareOrder::firstOrFail();
        $problems = [];

        $pages = ['/services', route('software.orders.success', $order->uuid, false), route('software.orders.failure', $order->uuid, false)];
        foreach ($pages as $path) {
            $english = $this->visibleText($this->get($path.'?lang=en')->assertOk()->getContent());
            $arabic = $this->visibleText($this->get($path.'?lang=ar')->assertOk()->getContent());

            if ($found = $this->arabicFragments($english)) {
                $problems["{$path} en"] = array_slice($found, 0, 3);
            }
            if ($found = $this->latinFragments($arabic)) {
                $problems["{$path} ar"] = array_slice($found, 0, 8);
            }
        }

        $this->assertSame([], $problems, json_encode($problems, JSON_UNESCAPED_UNICODE));
    }

    public function test_homepage_and_footer_are_one_language_in_both_languages(): void
    {
        $this->seedStore();

        $english = $this->visibleText($this->get('/?lang=en')->assertOk()->getContent());
        $arabic = $this->visibleText($this->get('/?lang=ar')->assertOk()->getContent());

        $this->assertSame([], $this->arabicFragments($english), 'Arabic text on the English homepage');
        $this->assertSame([], $this->latinFragments($arabic), 'English words on the Arabic homepage: '.json_encode($this->latinFragments($arabic)));
    }
}
