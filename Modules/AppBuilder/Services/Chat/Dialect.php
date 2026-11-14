<?php

namespace Modules\AppBuilder\Services\Chat;

use App\Services\IpGeolocationService;
use Illuminate\Http\Request;

/**
 * The visitor's country and the Arabic the chat speaks there: a Saudi customer hears Saudi Arabic, a Kuwaiti one
 * Gulf Arabic. The country comes from the IP (the site's offline database); a VPN or an unknown IP falls back to the
 * browser's time zone, which the page sends with the first message. The customer's own words come before the country:
 * the first message that shows a dialect sets it for the whole chat, so the answers never drift from one to another.
 */
final class Dialect
{
    private const BY_TIMEZONE = [
        'Africa/Cairo' => 'EG', 'Asia/Riyadh' => 'SA', 'Asia/Dubai' => 'AE', 'Asia/Kuwait' => 'KW', 'Asia/Qatar' => 'QA',
        'Asia/Bahrain' => 'BH', 'Asia/Muscat' => 'OM', 'Asia/Amman' => 'JO', 'Asia/Beirut' => 'LB', 'Asia/Damascus' => 'SY',
        'Asia/Gaza' => 'PS', 'Asia/Hebron' => 'PS', 'Asia/Baghdad' => 'IQ', 'Asia/Aden' => 'YE', 'Africa/Khartoum' => 'SD',
        'Africa/Tripoli' => 'LY', 'Africa/Tunis' => 'TN', 'Africa/Algiers' => 'DZ', 'Africa/Casablanca' => 'MA',
    ];

    private const SPOKEN = [
        'SA' => 'everyday Saudi Arabic (for example: أبغى، وش، زين، كذا، الحين)',
        'AE' => 'everyday Gulf Arabic', 'KW' => 'everyday Gulf Arabic', 'QA' => 'everyday Gulf Arabic', 'BH' => 'everyday Gulf Arabic', 'OM' => 'everyday Gulf Arabic',
        'JO' => 'everyday Levantine Arabic', 'PS' => 'everyday Levantine Arabic', 'SY' => 'everyday Levantine Arabic', 'LB' => 'everyday Levantine Arabic',
        'IQ' => 'everyday Iraqi Arabic',
        'MA' => 'simple Modern Standard Arabic (Maghrebi Darija is hard to read in writing)', 'DZ' => 'simple Modern Standard Arabic (Maghrebi Darija is hard to read in writing)', 'TN' => 'simple Modern Standard Arabic (Maghrebi Darija is hard to read in writing)',
    ];

    private const DEFAULT_SPOKEN = 'simple Egyptian Arabic';

    private const GULF = 'everyday Gulf Arabic';

    private const MAGHREB = 'simple Modern Standard Arabic (Maghrebi Darija is hard to read in writing)';

    /** Words that only one dialect uses in writing; a word shared by two dialects is not here. */
    private const WORDS = [
        'everyday Levantine Arabic' => ['بدي', 'بدك', 'بدنا', 'بدو', 'شو', 'هيك', 'هلق', 'هلأ', 'منيح', 'هون', 'هاد', 'تزبطو', 'تزبط'],
        'everyday Iraqi Arabic' => ['شكو', 'ماكو', 'هواي', 'شكد', 'هسه'],
        self::GULF => ['أبغى', 'ابغى', 'ابغا', 'أبي', 'وش', 'الحين', 'وايد', 'ودي', 'أبغاه', 'تكفى'],
        self::MAGHREB => ['بزاف', 'واش', 'كيفاش', 'بغيت', 'ديال', 'برشا', 'نحب'],
        self::DEFAULT_SPOKEN => ['عايز', 'عاوز', 'عايزه', 'ازاي', 'إزاي', 'دلوقتي', 'كده', 'بتاع', 'بتاعي', 'عشان', 'علشان'],
    ];

    /**
     * The browser looks the country up once (api.country.is, from the visitor's own connection) and sends it with the
     * first message; then the browser's time zone; then this server's own IP database. It only picks a dialect, so a
     * wrong value costs nothing.
     */
    public static function countryOf(Request $request): ?string
    {
        $fromBrowser = strtoupper((string) $request->input('country'));
        if (preg_match('/^[A-Z]{2}$/', $fromBrowser) === 1) {
            return $fromBrowser;
        }

        return self::BY_TIMEZONE[(string) $request->input('timezone')]
            ?? (($fromIp = app(IpGeolocationService::class)->getCountryFromIp((string) $request->ip())) ? strtoupper($fromIp) : null);
    }

    /**
     * How the chat writes Arabic for this customer: the dialect of the first of their messages that shows one, otherwise
     * the dialect of their country (Egyptian when unknown).
     *
     * @param  iterable<string>  $customerMessages  oldest first
     */
    public static function arabicFor(?string $country, iterable $customerMessages = []): string
    {
        $ofCountry = self::SPOKEN[strtoupper((string) $country)] ?? self::DEFAULT_SPOKEN;
        foreach ($customerMessages as $message) {
            $heard = self::heardIn((string) $message);
            if ($heard !== null) {
                // Gulf words from a customer in Saudi Arabia are Saudi Arabic
                return $heard === self::GULF && str_contains($ofCountry, 'Saudi') ? $ofCountry : $heard;
            }
        }

        return $ofCountry;
    }

    /** The dialect with the most of its own words in this message, or null when it shows none. */
    private static function heardIn(string $message): ?string
    {
        $words = preg_split('/[^\p{Arabic}]+/u', $message, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hits = array_filter(array_map(fn (array $own) => count(array_intersect($words, $own)), self::WORDS));
        arsort($hits);

        return array_key_first($hits);
    }
}
