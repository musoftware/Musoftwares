<?php

namespace Modules\Booking\app\Features\WhiteLabel\Services;

use App\Models\Tenant;
use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelDomain;
use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelAsset;
use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelTemplate;

class BookingWhiteLabelLimitsService
{
    /**
     * Verify if the tenant can add a new custom domain based on saas limits.
     */
    public function canAddDomain(int $tenantId): bool
    {
        $tenant = Tenant::find($tenantId);
        $maxDomains = $tenant->getLimit('booking-white-label.limits.max_custom_domains') ?? 1;
        $currentCount = WhiteLabelDomain::where('tenant_id', $tenantId)->count();
        return $currentCount < $maxDomains;
    }

    /**
     * Verify if the tenant can upload more white label assets.
     */
    public function canAddAsset(int $tenantId): bool
    {
        $tenant = Tenant::find($tenantId);
        $maxAssets = $tenant->getLimit('booking-white-label.limits.max_white_label_assets') ?? 5;
        $currentCount = WhiteLabelAsset::where('tenant_id', $tenantId)->count();
        return $currentCount < $maxAssets;
    }

    /**
     * Verify if the tenant can create more custom templates.
     */
    public function canAddTemplate(int $tenantId): bool
    {
        $tenant = Tenant::find($tenantId);
        $maxTemplates = $tenant->getLimit('booking-white-label.limits.max_custom_templates') ?? 10;
        $currentCount = WhiteLabelTemplate::where('tenant_id', $tenantId)->count();
        return $currentCount < $maxTemplates;
    }
}
