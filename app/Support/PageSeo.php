<?php

namespace App\Support;

/**
 * Title and description of a fixed public page in the current language, from lang/{ar,en}/seo.php.
 */
class PageSeo
{
    /** @return array{title: string, description: string} */
    public static function for(string $key): array
    {
        return [
            'title' => __("seo.{$key}.title").__('seo.suffix'),
            'description' => __("seo.{$key}.description"),
        ];
    }
}
