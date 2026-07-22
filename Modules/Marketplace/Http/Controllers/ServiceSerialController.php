<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ServiceSerial;
use Modules\Marketplace\Services\SoftwareLicenseService;

class ServiceSerialController extends Controller
{
    public function __construct(protected SoftwareLicenseService $softwareLicenseService) {}

    public function index(Request $request)
    {
        $serials = ServiceSerial::with('service')
            ->whereHas('service', function ($q) {
                $q->where('seller_id', auth()->id());
            })
            ->latest()
            ->paginate(15);

        return response()->json(['serials' => $serials]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:marketplace_services,id',
            'serial_key' => 'required|string|unique:marketplace_service_serials,serial_key',
        ]);

        $serial = ServiceSerial::create([
            'service_id' => $validated['service_id'],
            'serial_key' => $validated['serial_key'],
            'status' => 'available',
        ]);

        return response()->json(['success' => true, 'serial' => $serial]);
    }

    public function activateDevice(Request $request)
    {
        $validated = $request->validate([
            'serial_key' => 'required|string',
            'hwid' => 'required|string',
            'mac_address' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        try {
            $result = $this->softwareLicenseService->activateDevice(
                $validated['serial_key'],
                $validated['hwid'],
                $validated['mac_address'],
                $validated['device_name'] ?? null
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['activated' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
