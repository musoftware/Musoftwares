<?php

namespace Modules\AppBuilder\Services\Chat;

use App\Services\IpGeolocationService;
use Illuminate\Http\Request;

/**
 * The visitor's country and the Arabic the chat speaks there: a Saudi customer hears Saudi Arabic, a Kuwaiti one
 * Gulf Arabic. The country comes from the IP (the site's offline database); a VPN or an unknown IP falls back to the
 * browser's time zone, which the page sends with the first message.
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

    /** How the chat writes Arabic for a customer from this country (Egyptian when unknown). */
    public static function arabicFor(?string $country): string
    {
        return self::SPOKEN[strtoupper((string) $country)] ?? self::DEFAULT_SPOKEN;
    }
}
