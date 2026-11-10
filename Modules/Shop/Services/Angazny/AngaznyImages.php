<?php

namespace Modules\Shop\Services\Angazny;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/** Copies product photos to this server, so the store never depends on the supplier's image links. */
class AngaznyImages
{
    private const DISK = 'public_uploads';

    private const MAX_BYTES = 8 * 1024 * 1024;

    private const TIMEOUT_SECONDS = 30;

    /** Angazny's small thumbnails are often broken and too small for a product page. */
    private const SKIPPED_PATH = '500_500imgs';

    private const EXTENSIONS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /**
     * @param  list<string>  $urls
     * @return list<string> stored paths, leaving out any image that could not be copied
     */
    public function copy(array $urls): array
    {
        return array_values(array_filter(array_map(fn (string $url) => $this->copyOne($url), $urls)));
    }

    private function copyOne(string $url): ?string
    {
        if (! $this->isAllowed($url)) {
            return null;
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->get($url);
        } catch (ConnectionException $exception) {
            Log::warning('Angazny image download failed', ['url' => $url, 'error' => $exception->getMessage()]);

            return null;
        }

        return $this->store('angazny-'.sha1($url), $url, $response);
    }

    private function isAllowed(string $url): bool
    {
        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && parse_url($url, PHP_URL_HOST) === config('shop.angazny.image_host')
            && ! str_contains($url, self::SKIPPED_PATH);
    }

    public function store(string $fileName, string $url, Response $response): ?string
    {
        $type = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $extension = self::EXTENSIONS[$type] ?? null;

        if (! $response->successful() || $extension === null || strlen($response->body()) > self::MAX_BYTES) {
            Log::warning('Angazny image rejected', ['url' => $url, 'status' => $response->status(), 'type' => $type]);

            return null;
        }

        $path = 'shop/products/'.$fileName.'.'.$extension;
        Storage::disk(self::DISK)->put($path, $response->body());

        return $path;
    }
}
