<?php

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tools\Models\ActivatedDevice;
use Modules\Tools\Models\ToolLicense;

class LicenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * My Licenses — all issued license keys for the user.
     */
    public function index(): Response
    {
        $licenses = ToolLicense::where('user_id', auth()->id())
            ->with(['activeDevices'])
            ->latest()
            ->get()
            ->map(fn($lic) => [
                'id'              => $lic->id,
                'license_key'     => $lic->license_key,
                'status'          => $lic->status,
                'is_valid'        => $lic->isValid(),
                'expires_at'      => $lic->expires_at?->toDateString(),
                'last_validated'  => $lic->last_validated_at?->diffForHumans(),
                'tool'            => [
                    'slug'        => $lic->tool->slug,
                    'title'       => $lic->tool->title,
                    'icon_url'    => $lic->tool->icon_url,
                    'category'    => $lic->tool->category,
                ],
            ]);

        return Inertia::render('Tools/MyLicenses', [
            'licenses' => $licenses,
        ]);
    }

    /**
     * Device activations for a specific license.
     */
    public function devices(int $licenseId): Response
    {
        $license = ToolLicense::where('user_id', auth()->id())
            ->with(['devices'])
            ->findOrFail($licenseId);

        $devices = $license->devices->map(fn($d) => [
            'id'                  => $d->id,
            'device_name'         => $d->device_name ?? 'Unknown Device',
            'os'                  => $d->os ?? 'Unknown',
            'app_version'         => $d->app_version,
            'status'              => $d->status,
            'is_active'           => $d->isActive(),
            'last_seen_at'        => $d->last_seen_at?->diffForHumans() ?? 'Never',
            'ip_address'          => $d->ip_address,
            'hardware_fingerprint' => substr($d->hardware_fingerprint, 0, 8) . '...',
        ]);

        return Inertia::render('Tools/Devices', [
            'license' => [
                'id'           => $license->id,
                'license_key'  => $license->license_key,
                'tool'         => [
                    'slug'  => $license->tool->slug,
                    'title' => $license->tool->title,
                ],
            ],
            'devices' => $devices,
        ]);
    }

    /**
     * Revoke a specific device activation.
     */
    public function revokeDevice(int $licenseId, int $deviceId): RedirectResponse
    {
        $license = ToolLicense::where('user_id', auth()->id())->findOrFail($licenseId);
        $device  = ActivatedDevice::where('tool_license_id', $license->id)->findOrFail($deviceId);
        $device->revoke();

        return back()->with('success', 'Device activation revoked. That device can no longer use this license.');
    }
}
