<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ServiceSerial;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Services\SoftwareLicenseService;

class ServiceSerialController extends Controller
{
    public function __construct(protected SoftwareLicenseService $softwareLicenseService) {}

    public function index(Request $request)
    {
        $serials = ServiceSerial::with(['service', 'usedBy'])
            ->whereHas('service', function ($q) {
                $q->where('seller_id', auth()->id());
            })
            ->latest()
            ->paginate(15);

        $services = Service::where('seller_id', auth()->id())
            ->select('id', 'title', 'generate_serials')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['serials' => $serials, 'services' => $services]);
        }

        return \Inertia\Inertia::render('Marketplace/Seller/Serials', [
            'serials' => $serials,
            'services' => $services,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:marketplace_services,id',
            'serial_code' => 'required|string',
        ]);

        $service = Service::where('id', $validated['service_id'])
            ->where('seller_id', auth()->id())
            ->firstOrFail();

        $serial = ServiceSerial::create([
            'service_id' => $service->id,
            'serial_code' => trim($validated['serial_code']),
            'is_used' => false,
        ]);

        return response()->json(['success' => true, 'serial' => $serial]);
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:marketplace_services,id',
            'serial_codes' => 'required|array',
            'serial_codes.*' => 'string',
        ]);

        $service = Service::where('id', $validated['service_id'])
            ->where('seller_id', auth()->id())
            ->firstOrFail();

        $addedCount = $this->softwareLicenseService->addSerialsToService($service->id, $validated['serial_codes']);

        return response()->json(['success' => true, 'added_count' => $addedCount]);
    }
}

