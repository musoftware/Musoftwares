<?php

namespace Modules\Booking\app\Features\WhiteLabel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\WhiteLabel\Services\BookingWhiteLabelService;

class BrandingController extends Controller
{
    private BookingWhiteLabelService $service;

    public function __construct(BookingWhiteLabelService $service)
    {
        $this->service = $service;
    }

    public function getSettings(Request $request)
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        return response()->json($this->service->getSettings($tenantId));
    }

    public function updateSettings(Request $request)
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        
        $validated = $request->validate([
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'font_family' => 'nullable|string|max:50',
            'custom_css' => 'nullable|string|max:10000',
            'is_active' => 'boolean',
        ]);

        $settings = $this->service->updateSettings($tenantId, $validated);

        return response()->json($settings);
    }
}
