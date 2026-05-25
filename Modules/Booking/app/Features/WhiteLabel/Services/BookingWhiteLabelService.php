<?php

namespace Modules\Booking\app\Features\WhiteLabel\Services;

use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelSetting;
use Illuminate\Support\Facades\Cache;

class BookingWhiteLabelService
{
    /**
     * Get or create the white label settings for a given tenant.
     */
    public function getSettings(int $tenantId): WhiteLabelSetting
    {
        return Cache::rememberForever("white_label:settings:{$tenantId}", function () use ($tenantId) {
            return WhiteLabelSetting::firstOrCreate(
                ['tenant_id' => $tenantId],
                [
                    'primary_color' => '#000000',
                    'secondary_color' => '#ffffff',
                    'font_family' => 'Inter, sans-serif',
                    'is_active' => false,
                ]
            );
        });
    }

    /**
     * Update the white label settings for a tenant.
     */
    public function updateSettings(int $tenantId, array $data): WhiteLabelSetting
    {
        $settings = $this->getSettings($tenantId);
        $settings->update($data);

        Cache::forget("white_label:settings:{$tenantId}");

        // Dispatch event here if needed (e.g., WhiteLabelThemeUpdated)
        event(new \Modules\Booking\app\Features\WhiteLabel\Events\WhiteLabelThemeUpdated($tenantId));

        return $settings;
    }

    /**
     * Clear all white label caches for a tenant.
     */
    public function flushCache(int $tenantId): void
    {
        Cache::forget("white_label:settings:{$tenantId}");
        Cache::forget("white_label:assets:{$tenantId}");
        Cache::forget("white_label:domain:{$tenantId}");
    }
}
