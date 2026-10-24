<?php

namespace App\Support;

/** The structured data that says who owns the site. It is the same on every public page. */
class SiteSchema
{
    public static function json(): string
    {
        $home = url('/');

        return json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $home.'#organization',
                    'name' => 'Musoftware',
                    'url' => $home,
                    'founder' => ['@type' => 'Person', 'name' => 'Mahmoud Amin', 'url' => url('/about/mahmoud-amin')],
                    'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Suez', 'addressCountry' => 'EG'],
                    'contactPoint' => [['@type' => 'ContactPoint', 'contactType' => 'sales', 'telephone' => '+201015218548', 'availableLanguage' => ['ar', 'en']]],
                    'sameAs' => ['https://github.com/musoftware', 'https://www.linkedin.com/in/musoftwareuno/'],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $home.'#website',
                    'name' => 'Musoftware',
                    'url' => $home,
                    'inLanguage' => ['ar', 'en'],
                    'publisher' => ['@id' => $home.'#organization'],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?: '{}';
    }
}
