<?php

namespace Modules\Marketplace\Services;

use App\Helpers\ImageHelper;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Marketplace\Models\Service;

/**
 * Saves a service from the admin form. Packages and extras are updated in place, so the orders that
 * point at a package keep working; only the rows the admin removed are deleted.
 */
class AdminServiceWriter
{
    /** @param array<int, UploadedFile> $uploads */
    public function create(array $data, array $uploads, User $admin): Service
    {
        return DB::transaction(function () use ($data, $uploads, $admin) {
            $service = new Service(['slug' => Str::slug($data['title']) ?: null]);

            return $this->save($service, $data, $uploads, $admin);
        });
    }

    /** @param array<int, UploadedFile> $uploads */
    public function update(Service $service, array $data, array $uploads, User $admin): Service
    {
        $oldOwnerId = $service->seller_id;

        $saved = DB::transaction(fn () => $this->save($service, $data, $uploads, $admin));

        if ((int) $oldOwnerId !== (int) $saved->seller_id) {
            Log::info('marketplace.service.owner_changed', [
                'service_id' => $saved->id, 'from' => $oldOwnerId, 'to' => $saved->seller_id, 'by' => $admin->id,
            ]);
        }

        return $saved;
    }

    private function save(Service $service, array $data, array $uploads, User $admin): Service
    {
        $gallery = $this->gallery($service, $data, $uploads);

        $service->fill($this->attributes($data) + [
            'gallery' => $gallery,
            'thumbnail' => $this->thumbnail($service, $gallery, (int) $data['seller_id']),
        ] + $this->approval($service, $data['status'], $admin))->save();

        $this->syncRows($service->packages(), $data['packages'], fn (array $row, $current) => [
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => $row['price'],
            'old_price' => ! empty($row['old_price']) ? $row['old_price'] : null,
            'currency_id' => $row['currency_id'],
            'delivery_days' => $row['delivery_days'],
            'revisions' => $row['revisions'] ?? $current?->revisions ?? 2,
            'features' => $row['features'] ?? $current?->features ?? [],
        ]);

        $this->syncRows($service->extras(), $data['extras'] ?? [], fn (array $row) => [
            'title' => $row['title'],
            'price' => $row['price'],
            'duration_days' => $row['duration_days'] ?? 0,
        ]);

        return $service;
    }

    private function attributes(array $data): array
    {
        return [
            'seller_id' => $data['seller_id'],
            'category_id' => $data['category_id'],
            'status' => $data['status'],
            'title' => $data['title'],
            'title_translations' => $data['title_translations'] ?? null,
            'tagline' => $data['tagline'] ?? null,
            'tagline_translations' => $data['tagline_translations'] ?? null,
            'description' => $data['description'],
            'description_translations' => $data['description_translations'] ?? null,
            'auto_reply' => $data['auto_reply'] ?? null,
            'auto_reply_translations' => $data['auto_reply_translations'] ?? null,
            'auto_complete' => $data['auto_complete'] ?? false,
            'tags' => $data['tags'] ?? [],
            'faq' => $data['faq'] ?? [],
            'requirements' => $data['requirements'] ?? [],
            'video_url' => $data['video_url'] ?? null,
            'service_link' => $data['service_link'] ?? null,
            'is_free' => $data['is_free'] ?? false,
            'generate_serials' => $data['generate_serials'] ?? false,
            'allow_random_serial' => $data['allow_random_serial'] ?? false,
            'validity_days' => $data['validity_days'] ?? null,
            'referral_commission_from' => $data['referral_commission_from'] ?? 'fee',
            'referral_commission_percentage' => $data['referral_commission_percentage'] ?? null,
        ];
    }

    private function approval(Service $service, string $status, User $admin): array
    {
        if ($status !== 'active' || $service->approved_at) {
            return [];
        }

        return ['approved_at' => now(), 'approved_by' => $admin->id];
    }

    /** Only images the service already has can be kept, so the form cannot point at another file. */
    private function gallery(Service $service, array $data, array $uploads): array
    {
        $kept = array_values(array_intersect($data['kept_gallery'] ?? [], $service->gallery ?? []));
        $folder = 'services/'.$data['seller_id'];
        $added = array_map(fn (UploadedFile $image) => $image->store($folder, 'public_uploads'), $uploads);

        return array_merge($kept, $added);
    }

    private function thumbnail(Service $service, array $gallery, int $sellerId): ?string
    {
        $first = $gallery[0] ?? null;
        if (! $first) {
            return null;
        }
        if ($first === ($service->gallery[0] ?? null)) {
            return $service->getRawOriginal('thumbnail') ?: $first;
        }

        $relative = "services/{$sellerId}/thumb_".basename($first);
        $made = ImageHelper::createThumbnail(public_path('uploads/'.ltrim($first, '/')), public_path('uploads/'.$relative), 600, 400, 80);

        return $made ? $relative : $first;
    }

    /** Updates the rows that were sent with an id, adds the new ones, and deletes the rest. */
    private function syncRows(HasMany $relation, array $rows, callable $attributes): void
    {
        $keptIds = [];
        foreach ($rows as $row) {
            $current = empty($row['id']) ? null : (clone $relation)->find($row['id']);
            $record = $current ?? $relation->make();
            $record->fill($attributes($row, $current))->save();
            $keptIds[] = $record->getKey();
        }

        (clone $relation)->whereNotIn('id', $keptIds)->delete();
    }
}
