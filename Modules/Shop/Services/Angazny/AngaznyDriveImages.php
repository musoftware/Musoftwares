<?php

namespace Modules\Shop\Services\Angazny;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Shop\Models\ShopProduct;

/**
 * Adds every photo in a product's Drive folder to its gallery, after the photos it already has.
 * A product is done once its folder was read, so a photo the admin removes never comes back.
 */
class AngaznyDriveImages
{
    private const THUMBNAIL = 'https://drive.google.com/thumbnail?sz=w1200&id=';

    private const TIMEOUT_SECONDS = 60;

    private const DOWNLOAD_ATTEMPTS = 3;

    private const DOWNLOAD_RETRY_MILLISECONDS = 3000;

    private const PAUSE_MICROSECONDS = 300000;

    public function __construct(
        private readonly AngaznyClient $client,
        private readonly AngaznyDriveFolder $folder,
        private readonly AngaznyImages $images,
    ) {}

    /** @return array{products: int, images: int, failed: list<int>} */
    public function handle(int $limit): array
    {
        $report = ['products' => 0, 'images' => 0, 'failed' => []];

        $products = ShopProduct::whereNotNull('angazny_id')
            ->whereNull('angazny_drive_checked_at')
            ->orderByDesc('is_active')
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        foreach ($products as $product) {
            $report = $this->importOne($product, $report);
        }

        return $report;
    }

    private function importOne(ShopProduct $product, array $report): array
    {
        $ids = $this->photoIds($product);
        if ($ids === null) {
            $report['failed'][] = $product->angazny_id;

            return $report;
        }

        $paths = $this->download($product, $ids);
        if ($ids !== [] && $paths === []) {
            $report['failed'][] = $product->angazny_id;

            return $report;
        }

        $product->update(['images' => [...($product->images ?? []), ...$paths], 'angazny_drive_checked_at' => now()]);
        $report['products']++;
        $report['images'] += count($paths);

        return $report;
    }

    /** @return list<string>|null null when Angazny or Drive could not be read */
    private function photoIds(ShopProduct $product): ?array
    {
        try {
            $link = (string) ($this->client->product((int) $product->angazny_id)['product']['drive_link'] ?? '');
        } catch (RequestException|ConnectionException $exception) {
            Log::warning('Angazny product could not be read for Drive photos', ['angazny_id' => $product->angazny_id, 'error' => $exception->getMessage()]);

            return null;
        }

        return $link === '' ? [] : $this->folder->imageIds($link);
    }

    /** @return list<string> stored paths */
    private function download(ShopProduct $product, array $ids): array
    {
        $known = implode('|', $product->images ?? []);
        $fresh = array_filter($ids, fn (string $id) => ! str_contains($known, 'drive-'.$id.'.'));
        $paths = [];

        foreach (array_slice($fresh, 0, (int) config('shop.angazny.drive_max_images')) as $id) {
            $paths[] = $this->downloadOne($id);
            usleep(app()->runningUnitTests() ? 0 : self::PAUSE_MICROSECONDS);
        }

        return array_values(array_filter($paths));
    }

    private function downloadOne(string $id): ?string
    {
        $url = self::THUMBNAIL.$id;

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->retry(self::DOWNLOAD_ATTEMPTS, self::DOWNLOAD_RETRY_MILLISECONDS, throw: false)
                ->get($url);
        } catch (ConnectionException $exception) {
            Log::warning('Drive photo download failed', ['id' => $id, 'error' => $exception->getMessage()]);

            return null;
        }

        return $this->images->store('drive-'.$id, $url, $response);
    }
}
