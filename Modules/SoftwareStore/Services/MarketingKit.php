<?php

namespace Modules\SoftwareStore\Services;

use App\Support\UtmLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\SoftwareStore\Models\SoftwareProduct;

/**
 * Ready-to-post marketing text for every visible product, Arabic and English.
 * Everything is built from the product's own data (name, tagline, plans, first problem, FAQ answer about limits).
 * All wording lives in TEXT below; nothing here promises a result or invents a customer.
 */
class MarketingKit
{
    public const LANGUAGES = ['ar', 'en'];

    private const TEXT = [
        'ar' => [
            'brand' => 'موسوفت، استوديو برمجيات في السويس',
            'price_line' => 'السعر: من %s %s، %s.',
            'price_line_single' => 'السعر: %s %s، %s.',
            'details' => 'التفاصيل والصور:',
            'demo_with_media' => 'شوف %s وهو شغال قبل ما تشتري: صور حقيقية من المنتج وفيديو جولة.',
            'demo_without_media' => 'شوف تفاصيل %s وخطوات الشراء قبل ما تدفع.',
            'limit_label' => 'ملحوظة صريحة:',
            'wa_hello' => 'مرحبا، %s ممكن يناسب شغلك: %s',
            'wa_price' => 'السعر',
            'wa_link' => 'التفاصيل:',
            'email_subject' => '%s: %s',
            'email_hello' => 'أهلا،',
            'email_intro' => 'بعتلك الرسالة دي لأن %s ممكن يوفر عليك وقت في شغلك.',
            'email_more' => 'تقدر تشوف الصور والفيديو والأسعار هنا:',
            'email_reply' => 'لو عندك سؤال، رد على الرسالة دي أو كلمنا على واتساب.',
            'email_sign' => 'محمود أمين، موسوفت',
            'hash_brand' => '#موسوفت',
            'hash_country' => '#مصر',
            'hash_groups' => ['programs' => '#برامج_سطح_المكتب', 'systems' => '#أنظمة_إدارة', 'services' => '#خدمات_رقمية'],
            'hash_extra' => '#برمجة',
            'currency' => ['EGP' => 'جنيه', 'USD' => 'دولار'],
        ],
        'en' => [
            'brand' => 'Musoftware, a software studio in Suez, Egypt',
            'price_line' => 'Price: from %s %s, %s.',
            'price_line_single' => 'Price: %s %s, %s.',
            'details' => 'Details and pictures:',
            'demo_with_media' => 'See %s running before you buy: real pictures of the product and a tour video.',
            'demo_without_media' => 'See the details of %s and the buying steps before you pay.',
            'limit_label' => 'Honest note:',
            'wa_hello' => 'Hello, %s may fit your business: %s',
            'wa_price' => 'Price',
            'wa_link' => 'Details:',
            'email_subject' => '%s: %s',
            'email_hello' => 'Hello,',
            'email_intro' => 'I am writing because %s may save you time in your work.',
            'email_more' => 'You can see the pictures, the video and the prices here:',
            'email_reply' => 'If you have a question, reply to this email or message us on WhatsApp.',
            'email_sign' => 'Mahmoud Amin, Musoftware',
            'hash_brand' => '#Musoftware',
            'hash_country' => '#Egypt',
            'hash_groups' => ['programs' => '#DesktopSoftware', 'systems' => '#BusinessSoftware', 'services' => '#DigitalServices'],
            'hash_extra' => '#Software',
            'currency' => ['EGP' => 'EGP', 'USD' => 'USD'],
        ],
    ];

    /** Words in a question that mean the answer states what the product does not do yet (for example the e-receipt connector). */
    private const LIMIT_QUESTION = '/(ETA|e-receipt|إيصال|الفاتورة الإلكترونية)/iu';

    /** @return Collection<int, array<string, mixed>> one kit per active product */
    public function forAll(): Collection
    {
        return SoftwareProduct::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (SoftwareProduct $product) => $this->forProduct($product));
    }

    /** @return array<string, mixed> */
    public function forProduct(SoftwareProduct $product): array
    {
        $kit = [
            'slug' => $product->slug,
            'name_ar' => $product->localized('name', 'ar'),
            'name_en' => $product->localized('name', 'en'),
            'group' => $product->group(),
            'url' => $product->purchaseUrl(),
            'links' => $this->links($product),
        ];

        foreach (self::LANGUAGES as $lang) {
            $kit[$lang] = [
                'posts' => $this->posts($product, $lang),
                'whatsapp' => $this->whatsapp($product, $lang),
                'email' => $this->email($product, $lang),
                'hashtags' => $this->hashtags($product, $lang),
            ];
        }

        return $kit;
    }

    /** @return array<string, string> channel => link with utm parameters */
    public function links(SoftwareProduct $product): array
    {
        return collect(array_keys(UtmLink::CHANNELS))
            ->mapWithKeys(fn (string $channel) => [$channel => UtmLink::make($product->purchaseUrl(), $channel, $product->slug)])
            ->all();
    }

    /** @return array{problem: string, price: string, demo: string} */
    public function posts(SoftwareProduct $product, string $lang): array
    {
        $text = self::TEXT[$lang];
        $link = UtmLink::make($product->purchaseUrl(), 'facebook', $product->slug);
        $name = (string) $product->localized('name', $lang);
        $tagline = (string) $product->localized('tagline', $lang);
        $limit = $this->limitLine($product, $lang);
        $tail = array_filter([$limit, $text['details'].' '.$link, $text['brand']]);

        $problem = $this->firstProblem($product, $lang);
        $opening = $problem ? $problem['title'].': '.$problem['body'] : $tagline;

        return [
            'problem' => implode("\n\n", array_filter([$opening, $name.': '.$tagline, $this->priceLine($product, $lang), ...$tail])),
            'price' => implode("\n\n", array_filter([$name.': '.$tagline, $this->priceLine($product, $lang), ...$tail])),
            'demo' => implode("\n\n", array_filter([sprintf($this->hasMedia($product) ? $text['demo_with_media'] : $text['demo_without_media'], $name), $tagline, $this->priceLine($product, $lang), ...$tail])),
        ];
    }

    public function whatsapp(SoftwareProduct $product, string $lang): string
    {
        $text = self::TEXT[$lang];
        $link = UtmLink::make($product->purchaseUrl(), 'whatsapp', $product->slug);

        return implode("\n", array_filter([
            sprintf($text['wa_hello'], $product->localized('name', $lang), $product->localized('tagline', $lang)),
            $this->priceLine($product, $lang),
            $this->limitLine($product, $lang),
            $text['wa_link'].' '.$link,
        ]));
    }

    /** @return array{subject: string, body: string} */
    public function email(SoftwareProduct $product, string $lang): array
    {
        $text = self::TEXT[$lang];
        $name = (string) $product->localized('name', $lang);
        $link = UtmLink::make($product->purchaseUrl(), 'email', $product->slug);

        return [
            'subject' => sprintf($text['email_subject'], $name, $product->localized('tagline', $lang)),
            'body' => implode("\n\n", array_filter([
                $text['email_hello'],
                sprintf($text['email_intro'], $name),
                $product->localized('tagline', $lang),
                $this->priceLine($product, $lang),
                $this->limitLine($product, $lang),
                $text['email_more']."\n".$link,
                $text['email_reply'],
                $text['email_sign'],
            ])),
        ];
    }

    /** @return list<string> exactly five tags */
    public function hashtags(SoftwareProduct $product, string $lang): array
    {
        $text = self::TEXT[$lang];
        $name = (string) $product->localized('name', $lang);
        $nameTag = '#'.($lang === 'ar'
            ? preg_replace('/\s+/u', '_', trim(preg_replace('/[^\p{L}\p{N}\s]+/u', '', $name)))
            : Str::studly(preg_replace('/[^A-Za-z0-9\s]+/', '', $name)));

        return [$text['hash_brand'], $text['hash_country'], $text['hash_groups'][$product->group()], $nameTag, $text['hash_extra']];
    }

    private function priceLine(SoftwareProduct $product, string $lang): string
    {
        $text = self::TEXT[$lang];
        $currency = $product->saleCurrency();
        $period = $product->landingCopy($lang)['plans']['period'] ?? trans('software.per_year', [], $lang);
        $format = $product->hasPlans() ? $text['price_line'] : $text['price_line_single'];

        return sprintf($format, $product->money($product->startingPrice()), $text['currency'][$currency] ?? $currency, $period);
    }

    /** The answer to the product page's own question about the e-receipt (or similar), so the post says what the page says. */
    private function limitLine(SoftwareProduct $product, string $lang): ?string
    {
        foreach ($product->landingCopy($lang)['faq']['items'] ?? [] as $item) {
            if (preg_match(self::LIMIT_QUESTION, (string) ($item['q'] ?? ''))) {
                return self::TEXT[$lang]['limit_label'].' '.$this->wholeSentences((string) $item['a'], 280);
            }
        }

        return null;
    }

    /** The first sentences that fit in $max characters, never cut in the middle of a sentence. */
    private function wholeSentences(string $text, int $max): string
    {
        $sentences = preg_split('/(?<=[.!?؟])\s+/u', trim($text)) ?: [trim($text)];
        $kept = '';
        foreach ($sentences as $sentence) {
            if ($kept !== '' && mb_strlen($kept.' '.$sentence) > $max) {
                break;
            }
            $kept = trim($kept.' '.$sentence);
        }

        return $kept;
    }

    /** @return array{title: string, body: string}|null */
    private function firstProblem(SoftwareProduct $product, string $lang): ?array
    {
        $item = $product->landingCopy($lang)['problems']['items'][0] ?? null;

        return $item && ! empty($item['title']) && ! empty($item['body']) ? ['title' => (string) $item['title'], 'body' => (string) $item['body']] : null;
    }

    private function hasMedia(SoftwareProduct $product): bool
    {
        return $product->galleryPaths() !== [] || ! empty($product->video_path) || ! empty($product->video_url);
    }
}
