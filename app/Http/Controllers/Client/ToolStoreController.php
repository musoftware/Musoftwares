<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SerialSoftwareLicense;
use App\Models\SerialUserDevice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The client's licenses and devices. Programs themselves are sold in the software store (/services).
 */
class ToolStoreController extends Controller
{
    /**
     * User's owned software licenses and linked devices.
     */
    public function myLicenses(Request $request): Response
    {
        $user = $request->user();

        $licenses = SerialSoftwareLicense::with('software')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (SerialSoftwareLicense $license) {
                return [
                    'id'            => $license->id,
                    'software_name' => $license->software?->name ?? 'Unknown',
                    'status'        => $license->isActive() ? 'active' : ($license->status ?: 'inactive'),
                    'expires_at'    => $license->expires_at?->toIso8601String(),
                    'max_devices'   => $license->max_devices,
                    'created_at'    => $license->created_at?->toIso8601String(),
                ];
            });

        $linkedDevices = SerialUserDevice::with('devices.software')
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (SerialUserDevice $ud) {
                $primaryDevice = $ud->devices->first();

                return [
                    'id'              => $ud->id,
                    'device_id'       => $ud->device_id,
                    'status'          => $ud->status,
                    'expires_at'      => $ud->expires_at?->toIso8601String(),
                    'machine_name'    => $primaryDevice?->machine_name,
                    'os_version'      => $primaryDevice?->os_version,
                    'last_check_date' => $primaryDevice?->last_check_date?->toIso8601String(),
                    'software_name'   => $primaryDevice?->software?->name ?? 'Assigned Device',
                ];
            });

        return Inertia::render('Store/Tools/MyLicenses', [
            'licenses'      => $licenses,
            'linkedDevices' => $linkedDevices,
        ]);
    }
}
