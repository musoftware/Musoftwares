<?php

namespace Modules\Booking\app\Features\WhiteLabel\Services;

use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class WhiteLabelAssetManager
{
    /**
     * Upload an asset and associate it with a tenant.
     */
    public function uploadAsset(int $tenantId, UploadedFile $file, string $type): WhiteLabelAsset
    {
        $disk = config('filesystems.default'); // usually 's3' or 'public'
        
        $path = $file->store("tenants/{$tenantId}/white-label/{$type}", $disk);
        $url = Storage::disk($disk)->url($path);

        // Invalidate cache
        app(BookingWhiteLabelService::class)->flushCache($tenantId);

        return WhiteLabelAsset::updateOrCreate(
            ['tenant_id' => $tenantId, 'type' => $type],
            [
                'path' => $path,
                'disk' => $disk,
                'url' => $url,
            ]
        );
    }

    /**
     * Get a specific asset URL for a tenant.
     */
    public function getAssetUrl(int $tenantId, string $type): ?string
    {
        $asset = WhiteLabelAsset::where('tenant_id', $tenantId)->where('type', $type)->first();
        return $asset ? $asset->url : null;
    }
}
