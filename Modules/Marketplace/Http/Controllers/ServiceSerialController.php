<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceSerial;
use Modules\Marketplace\Services\SoftwareLicenseService;

class ServiceSerialController extends Controller
{
    public function __construct(protected SoftwareLicenseService $softwareLicenseService) {}

    public function index(Request $request): Response|JsonResponse
    {
        $userId = $request->user()?->id;

        $serials = ServiceSerial::with(['service', 'usedBy'])
            ->whereHas('service', fn ($q) => $q->where('seller_id', $userId))
            ->latest()
            ->paginate(15);

        $services = Service::where('seller_id', $userId)
            ->select('id', 'title', 'generate_serials')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['serials' => $serials, 'services' => $services]);
        }

        return Inertia::render('Marketplace/Seller/Serials', [
            'serials' => $serials,
            'services' => $services,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:marketplace_services,id',
            'serial_code' => 'required|string',
        ]);

        $service = Service::where('id', $validated['service_id'])
            ->where('seller_id', $request->user()?->id)
            ->firstOrFail();

        $serial = ServiceSerial::create([
            'service_id' => $service->id,
            'serial_code' => trim($validated['serial_code']),
            'is_used' => false,
        ]);

        return response()->json(['success' => true, 'serial' => $serial]);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:marketplace_services,id',
            'serial_codes' => 'required|array',
            'serial_codes.*' => 'string',
        ]);

        $service = Service::where('id', $validated['service_id'])
            ->where('seller_id', $request->user()?->id)
            ->firstOrFail();

        $addedCount = $this->softwareLicenseService->addSerialsToService($service->id, $validated['serial_codes']);

        return response()->json(['success' => true, 'added_count' => $addedCount]);
    }
}


