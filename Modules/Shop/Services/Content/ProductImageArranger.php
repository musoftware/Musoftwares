<?php

namespace Modules\Shop\Services\Content;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Shop\Models\ShopProduct;

/**
 * Links each colour to its photo and orders the gallery. The main photo stays first.
 * Claude's answer is only ever used to point at photos this product already has.
 */
class ProductImageArranger
{
    private const DISK = 'public_uploads';

    private const MAX_IMAGES = 24;

    private const MAX_BYTES = 4500000;

    private const MEDIA_TYPES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

    public function __construct(private readonly ProductImageMatcher $matcher) {}

    /** @return array{arranged: int, failed: list<int>} */
    public function handle(int $limit, ?int $angaznyId = null): array
    {
        $report = ['arranged' => 0, 'failed' => []];

        foreach ($this->pending($limit, $angaznyId) as $product) {
            $report = $this->arrange($product, $report);
        }

        return $report;
    }

    private function pending(int $limit, ?int $angaznyId)
    {
        return ShopProduct::whereNotNull('angazny_id')
            ->whereNotNull('angazny_drive_checked_at')
            ->when($angaznyId, fn ($query) => $query->where('angazny_id', $angaznyId))
            ->when(! $angaznyId, fn ($query) => $query->whereNull('images_arranged_at')->orderByDesc('is_active')->inRandomOrder())
            ->limit($limit)
            ->get();
    }

    private function arrange(ShopProduct $product, array $report): array
    {
        $photos = $this->readablePhotos($product->images ?? []);

        if (count($photos) < 2) {
            $product->update(['images_arranged_at' => now()]);

            return $report;
        }

        try {
            $answer = $this->matcher->match($product->name, $product->colors ?? [], $photos);
        } catch (ProductCopyUnavailable $exception) {
            Log::warning('Product photos were not arranged', ['product_id' => $product->id, 'error' => $exception->getMessage()]);
            $report['failed'][] = (int) $product->angazny_id;

            return $report;
        }

        $this->save($product, $photos, $answer);
        $report['arranged']++;

        return $report;
    }

    /** @return array<int, array{path: string, media_type: string, data: string}> keyed by photo number, starting at 1 */
    private function readablePhotos(array $paths): array
    {
        $photos = [];

        foreach (array_slice($paths, 0, self::MAX_IMAGES) as $path) {
            $photo = $this->readPhoto($path);
            if ($photo !== null) {
                $photos[count($photos) + 1] = $photo;
            }
        }

        return $photos;
    }

    private function readPhoto(string $path): ?array
    {
        $disk = Storage::disk(self::DISK);
        $mediaType = self::MEDIA_TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;

        if ($mediaType === null || ! $disk->exists($path) || $disk->size($path) > self::MAX_BYTES) {
            return null;
        }

        return ['path' => $path, 'media_type' => $mediaType, 'data' => base64_encode((string) $disk->get($path))];
    }

    private function save(ShopProduct $product, array $photos, array $answer): void
    {
        $images = $product->images;
        $ordered = array_values(array_unique(array_filter(array_map(
            fn (int $number) => $number === 1 ? null : ($photos[$number]['path'] ?? null),
            $answer['gallery_order'],
        ))));

        $product->update([
            'images' => array_values(array_unique([$images[0], ...$ordered, ...$images])),
            'color_images' => $this->colorImages($product->colors ?? [], $photos, $answer['color_images']),
            'images_arranged_at' => now(),
        ]);
    }

    /** @return array<string, string> colour name => stored path */
    private function colorImages(array $colors, array $photos, array $pairs): array
    {
        return collect($pairs)
            ->filter(fn (array $pair) => in_array($pair['color'], $colors, true) && isset($photos[$pair['image']]))
            ->mapWithKeys(fn (array $pair) => [$pair['color'] => $photos[$pair['image']]['path']])
            ->all();
    }
}
