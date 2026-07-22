<?php

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Models\ServiceLandingPage;
use Modules\Marketplace\Models\ServiceLandingPageVariant;
use Modules\Marketplace\Models\ServiceLandingPageCtaVariant;
use Modules\Marketplace\Models\ServiceLandingPageAbMetric;
use Illuminate\Http\Request;

class AbTestingService
{
    /**
     * Resolve active variant for landing page view based on traffic split percentage.
     */
    public function resolveActiveVariant(ServiceLandingPage $landingPage): ?ServiceLandingPageVariant
    {
        $variants = ServiceLandingPageVariant::where('landing_page_id', $landingPage->id)
            ->where('is_active', true)
            ->get();

        if ($variants->isEmpty()) {
            return null;
        }

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($variants as $variant) {
            $cumulative += $variant->traffic_weight ?? (100 / $variants->count());
            if ($rand <= $cumulative) {
                return $variant;
            }
        }

        return $variants->first();
    }

    /**
     * Track event metric for A/B testing analytics.
     */
    public function trackMetric(int $landingPageId, string $eventType, ?int $variantId = null, array $extraData = []): ServiceLandingPageAbMetric
    {
        $metric = ServiceLandingPageAbMetric::create([
            'landing_page_id' => $landingPageId,
            'variant_id' => $variantId,
            'event_type' => $eventType,
            'visitor_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'scroll_depth' => $extraData['scroll_depth'] ?? null,
            'time_on_page' => $extraData['time_on_page'] ?? null,
            'conversion_value' => $extraData['conversion_value'] ?? 0,
            'created_at' => now('Africa/Cairo'),
        ]);

        return $metric;
    }
}
