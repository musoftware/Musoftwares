<?php

namespace Modules\Shortlink\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Shortlink\Models\ShortlinkLink;

class ShortlinkService
{
    /** Base62 code length (~60 bits of entropy at length 10). */
    public const CODE_LENGTH = 10;

    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const MAX_ATTEMPTS = 5;

    /**
     * Create a new short link. The destination URL is stored verbatim
     * (including any signature), and a high-entropy unique short code is
     * generated relying on the DB unique constraint + retry for race safety.
     *
     * @param array{
     *     destination_url: string,
     *     label?: string|null,
     *     created_by_user_id?: int|null,
     *     is_active?: bool,
     *     expires_at?: \Illuminate\Support\Carbon|string|null
     * } $data
     */
    public function create(array $data, ?object $source = null): ShortlinkLink
    {
        $attempts = 0;

        do {
            $link = new ShortlinkLink();
            $link->short_code = $this->generateUniqueCode();
            $link->destination_url = $data['destination_url'];
            $link->label = $data['label'] ?? null;
            $link->created_by_user_id = $data['created_by_user_id'] ?? auth()->id();
            $link->is_active = $data['is_active'] ?? true;
            $link->expires_at = $data['expires_at'] ?? null;

            if ($source) {
                $link->source()->associate($source);
            }

            try {
                $link->save();

                return $link;
            } catch (QueryException $e) {
                if (!$this->isUniqueViolation($e)) {
                    throw $e;
                }
                $attempts++;
            }
        } while ($attempts < self::MAX_ATTEMPTS);

        throw new \RuntimeException(
            'Unable to allocate a unique shortlink code after ' . self::MAX_ATTEMPTS . ' attempts.'
        );
    }

    /**
     * Find an existing link for the given destination URL, or create a new one.
     *
     * Use this for generated/shared URLs (e.g. signed board URLs) where the
     * same deterministic destination is rendered repeatedly — it dedupes so we
     * don't create a new short link on every page view.
     *
     * @param array{
     *     label?: string|null,
     *     created_by_user_id?: int|null,
     *     is_active?: bool,
     *     expires_at?: \Illuminate\Support\Carbon|string|null
     * } $data
     */
    public function findOrCreateForDestination(string $url, array $data = [], ?object $source = null): ShortlinkLink
    {
        $existing = ShortlinkLink::query()
            ->where('destination_url', $url)
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->create(array_merge(['destination_url' => $url], $data), $source);
    }

    /**
     * Resolve a code into a usable (active, non-expired, non-deleted) link.
     * Returns null when the link is not resolvable for any reason.
     */
    public function resolve(string $code): ?ShortlinkLink
    {
        return ShortlinkLink::query()
            ->where('short_code', $code)
            ->active()
            ->notExpired()
            ->first();
    }

    /**
     * Atomically increment the click counter using a single UPDATE query
     * to stay correct under concurrent redirects.
     */
    public function recordClick(ShortlinkLink $link): void
    {
        ShortlinkLink::whereKey($link->id)->increment('clicks');
    }

    /**
     * Build the public short URL for a link.
     */
    public function shortUrl(ShortlinkLink $link): string
    {
        return route('shortlink.redirect', ['code' => $link->short_code]);
    }

    /**
     * Generate a cryptographically random base62 code.
     * Uniqueness is enforced at the DB level (unique index + retry in create()).
     */
    public function generateUniqueCode(int $length = self::CODE_LENGTH): string
    {
        $maxIndex = strlen(self::ALPHABET) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= self::ALPHABET[random_int(0, $maxIndex)];
        }

        return $code;
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $sqlstate = $e->errorInfo[0] ?? null;

        // SQLSTATE 23000 = integrity constraint violation (covers unique key
        // violations on both MySQL and SQLite).
        return $sqlstate === '23000';
    }
}
