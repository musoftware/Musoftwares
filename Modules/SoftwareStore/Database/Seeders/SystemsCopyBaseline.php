<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Fingerprints of the store copy that was seeded before for the 15 first ready-made systems
 * (the versions in commits af1711ad and be0beba0). A stored block that still has one of these
 * fingerprints was never edited in the admin, so it is safe to replace with the current copy.
 * The data file holds, per slug, the field (tagline_en...) or "lang.block" key and its known fingerprints.
 */
class SystemsCopyBaseline
{
    private const FILE = __DIR__.'/data/systems-copy-baseline.json';

    /** @return array<string, array<string, array<int, string>>> slug => key => fingerprints */
    public static function fingerprints(): array
    {
        return json_decode((string) file_get_contents(self::FILE), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array<int, string> */
    public static function slugs(): array
    {
        return array_keys(self::fingerprints());
    }

    /** The same text gives the same fingerprint, whatever order the database keeps the keys in. */
    public static function fingerprint(mixed $value): string
    {
        return sha1(json_encode(self::canonical($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(self::canonical(...), $value);
    }
}
