<?php

namespace App\Support;

/**
 * One rule for the two language versions of a public page, shared by every layout:
 * English lives at the plain address and Arabic at the same address with ?lang=ar.
 * Search engines crawl without a session, so the plain address is always English for them.
 */
class LocaleUrl
{
    /** The address of the page in the language it is shown in now. */
    public static function canonical(string $url): string
    {
        return app()->getLocale() === 'ar' ? self::arabic($url) : $url;
    }

    /** @return array<string, string> hreflang => address */
    public static function alternates(string $url): array
    {
        return ['ar' => self::arabic($url), 'en' => $url, 'x-default' => $url];
    }

    private static function arabic(string $url): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').'lang=ar';
    }
}
