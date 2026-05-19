<?php

namespace Modules\Tools\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tools\Models\ActivatedDevice;
use Modules\Tools\Models\Tool;
use Modules\Tools\Models\ToolLicense;

/**
 * Called by the Python desktop app:
 * 1. On first launch — activate_device()
 * 2. On every launch — check() + heartbeat()
 * 3. Periodically — heartbeat() every 30 min
 */
class LicenseController extends Controller
{
    /**
     * POST /api/tools/license/activate
     * Body: { license_key, hardware_fingerprint, device_name, os, app_version }
     */
    public function activate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'license_key'          => ['required', 'uuid'],
            'hardware_fingerprint' => ['required', 'string', 'max:128'],
            'device_name'          => ['nullable', 'string', 'max:100'],
            'os'                   => ['nullable', 'string', 'in:windows,mac,linux'],
            'app_version'          => ['nullable', 'string', 'max:20'],
        ]);

        $license = ToolLicense::where('license_key', $data['license_key'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$license) {
            return response()->json(['success' => false, 'error' => 'license_not_found'], 404);
        }

        if (!$license->isValid()) {
            return response()->json(['success' => false, 'error' => 'license_expired_or_revoked'], 403);
        }

        // Check if device already registered
        $existing = ActivatedDevice::where('tool_license_id', $license->id)
            ->where('hardware_fingerprint', $data['hardware_fingerprint'])
            ->first();

        if ($existing) {
            if ($existing->status === 'banned') {
                return response()->json(['success' => false, 'error' => 'device_banned'], 403);
            }
            // Re-activate revoked device if under limit
            if ($existing->status === 'revoked') {
                if (!$license->canActivateDevice()) {
                    return response()->json(['success' => false, 'error' => 'device_limit_reached', 'max' => $license->max_devices], 403);
                }
                $existing->update(['status' => 'active', 'revoked_at' => null]);
            }
            $existing->touchHeartbeat($request->ip(), $data['app_version'] ?? '');
            $license->touchValidation();
            return $this->activationSuccess($license, $existing);
        }

        // New device
        if (!$license->canActivateDevice()) {
            return response()->json([
                'success' => false,
                'error'   => 'device_limit_reached',
                'current' => $license->activeDevices()->count(),
            ], 403);
        }

        $device = ActivatedDevice::create([
            'tool_license_id'      => $license->id,
            'user_id'              => $request->user()->id,
            'hardware_fingerprint' => $data['hardware_fingerprint'],
            'device_name'          => $data['device_name'] ?? null,
            'os'                   => $data['os'] ?? 'windows',
            'app_version'          => $data['app_version'] ?? null,
            'status'               => 'active',
            'last_seen_at'         => now(),
            'ip_address'           => $request->ip(),
        ]);

        $license->touchValidation();

        return $this->activationSuccess($license, $device);
    }

    /**
     * POST /api/tools/license/check
     * Quick validation — called on every app launch.
     */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'license_key'          => ['required', 'uuid'],
            'hardware_fingerprint' => ['required', 'string'],
        ]);

        $license = ToolLicense::where('license_key', $data['license_key'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$license || !$license->isValid()) {
            return response()->json(['valid' => false, 'error' => 'invalid_or_expired'], 403);
        }

        $device = ActivatedDevice::where('tool_license_id', $license->id)
            ->where('hardware_fingerprint', $data['hardware_fingerprint'])
            ->first();

        if (!$device || !$device->isActive()) {
            return response()->json(['valid' => false, 'error' => 'device_not_activated'], 403);
        }

        $device->touchHeartbeat($request->ip(), $request->input('app_version', ''));
        $license->touchValidation();

        return response()->json([
            'valid'      => true,
            'expires_at' => $license->expires_at?->toIso8601String(),
            'grace_days' => 3, // offline grace period
        ]);
    }

    /**
     * POST /api/tools/license/heartbeat
     * Called every 30 minutes during active use.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'license_key'          => ['required', 'uuid'],
            'hardware_fingerprint' => ['required', 'string'],
            'app_version'          => ['nullable', 'string'],
        ]);

        $license = ToolLicense::where('license_key', $data['license_key'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$license || !$license->isValid()) {
            return response()->json(['alive' => false]);
        }

        ActivatedDevice::where('tool_license_id', $license->id)
            ->where('hardware_fingerprint', $data['hardware_fingerprint'])
            ->where('status', 'active')
            ->update([
                'last_seen_at' => now(),
                'ip_address'   => $request->ip(),
                'app_version'  => $data['app_version'] ?? null,
            ]);

        return response()->json(['alive' => true]);
    }

    // ─── Helper ─────────────────────────────────────────────────────────────────

    private function activationSuccess(ToolLicense $license, ActivatedDevice $device): JsonResponse
    {
        return response()->json([
            'success'    => true,
            'device_id'  => $device->id,
            'expires_at' => $license->expires_at?->toIso8601String(),
            ]);
    }
}
