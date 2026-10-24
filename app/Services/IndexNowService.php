<?php

namespace App\Services;

use App\Models\AdminSettings;
use App\Models\BlogArticle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\SoftwareStore\Models\SoftwareProduct;

/**
 * Tells IndexNow search engines (Bing, Yandex and others) about new or changed public pages.
 * A URL is sent again only when its content stamp changed, so a daily run with no news sends nothing.
 */
class IndexNowService
{
    private const ENDPOINT = 'https://api.indexnow.org/indexnow';

    private const KEY_SETTING = 'indexnow_key';

    private const SENT_SETTING = 'indexnow_submitted';

    private const ENABLED_SETTING = 'indexnow_enabled';

    private const CHUNK_SIZE = 500;

    /** The key is made once and kept in the settings, never written in code. */
    public function key(): string
    {
        $key = (string) AdminSettings::GetValue(self::KEY_SETTING, '');

        if (! preg_match('/^[a-f0-9]{32}$/', $key)) {
            $key = bin2hex(random_bytes(16));
            AdminSettings::SetValue(self::KEY_SETTING, $key);
        }

        return $key;
    }

    /** Off unless switched on in the settings; on by default only in production so local work never pings. */
    public function enabled(): bool
    {
        $setting = AdminSettings::GetValue(self::ENABLED_SETTING);

        return $setting === null ? app()->isProduction() : filter_var($setting, FILTER_VALIDATE_BOOLEAN);
    }

    public function keyLocation(): string
    {
        return url('/'.$this->key().'.txt');
    }

    /** @return array<string, string> public url => stamp that changes when the page content changes */
    public function publicPages(): array
    {
        $products = SoftwareProduct::where('is_active', true)->get(['slug', 'updated_at']);
        $articles = BlogArticle::published()->get(['slug', 'updated_at']);

        $pages = [
            url('/') => $this->stamp($products->max('updated_at')),
            route('services.index') => $this->stamp($products->max('updated_at')),
            route('blog.index') => $this->stamp($articles->max('updated_at')),
        ];

        foreach ($products as $product) {
            $pages[route('website-services.show', $product->slug)] = $this->stamp($product->updated_at);
        }
        foreach ($articles as $article) {
            $pages[route('blog.show', $article->slug)] = $this->stamp($article->updated_at);
        }

        return $pages;
    }

    /** @return array<int, string> urls that are new or changed since the last successful submit */
    public function changedUrls(bool $force = false): array
    {
        $sent = $force ? [] : $this->sent();

        return array_keys(array_filter($this->publicPages(), fn (string $stamp, string $url) => ($sent[$url] ?? null) !== $stamp, ARRAY_FILTER_USE_BOTH));
    }

    /** Submits what changed and remembers only what the engine accepted. Returns the number of urls accepted. */
    public function submitChanged(bool $force = false): int
    {
        $pages = $this->publicPages();
        $accepted = 0;

        foreach (array_chunk($this->changedUrls($force), self::CHUNK_SIZE) as $chunk) {
            if (! $this->post($chunk)) {
                break;
            }
            $this->remember(array_intersect_key($pages, array_flip($chunk)));
            $accepted += count($chunk);
        }

        return $accepted;
    }

    /** @param  array<int, string>  $urls */
    private function post(array $urls): bool
    {
        try {
            $response = Http::timeout(20)->acceptJson()->post(self::ENDPOINT, [
                'host' => parse_url(url('/'), PHP_URL_HOST),
                'key' => $this->key(),
                'keyLocation' => $this->keyLocation(),
                'urlList' => array_values($urls),
            ]);
        } catch (\Throwable $e) {
            Log::warning('IndexNow request failed.', ['error' => $e->getMessage()]);

            return false;
        }

        if (! in_array($response->status(), [200, 202], true)) {
            Log::warning('IndexNow rejected the submit.', ['status' => $response->status(), 'body' => Str::limit($response->body(), 300)]);

            return false;
        }

        return true;
    }

    /** @return array<string, string> */
    private function sent(): array
    {
        $decoded = json_decode((string) AdminSettings::GetValue(self::SENT_SETTING, '{}'), true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param  array<string, string>  $pages */
    private function remember(array $pages): void
    {
        AdminSettings::SetValue(self::SENT_SETTING, json_encode($pages + $this->sent(), JSON_UNESCAPED_SLASHES));
    }

    private function stamp(mixed $date): string
    {
        return $date ? (string) $date : 'none';
    }
}
