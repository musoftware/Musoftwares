<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Core\Models\SerialDevice;
use Modules\Core\Models\SerialSoftware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Serial Device Check-In API — called by client software on startup.
 *
 * Flow:
 * 1. Software sends program_name + device_id + optional env info
 * 2. SerialSoftware auto-created if new program_name
 * 3. SerialDevice auto-created with software's default_status if new device
 * 4. If device already exists, env info is updated + last_check_date refreshed
 * 5. Response: { "status": "active" } or { "status": "inactive" }
 * 6. Software reads status and enables/disables accordingly
 *
 * No authentication required — the device_id itself is the identity.
 */
class SerialDeviceController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_name'      => ['required', 'string', 'max:255'],
            'device_id'         => ['required', 'string', 'max:255'],
            'user_name'         => ['nullable', 'string', 'max:255'],
            'user_domain'       => ['nullable', 'string', 'max:255'],
            'machine_name'      => ['nullable', 'string', 'max:255'],
            'os_version'        => ['nullable', 'string', 'max:255'],
            'framework_version' => ['nullable', 'string', 'max:255'],
            'current_directory' => ['nullable', 'string'],
            'current_culture'   => ['nullable', 'string', 'max:100'],
            'current_ui_culture'=> ['nullable', 'string', 'max:100'],
        ]);

        // Auto-create software entry if this is the first time this program checks in
        $software = SerialSoftware::firstOrCreate(
            ['name' => $validated['program_name']],
            ['default_status' => SerialSoftware::DEFAULT_STATUS_ACTIVE]
        );

        // Auto-create device if first check-in from this machine for this software
        $device = SerialDevice::firstOrCreate(
            [
                'serial_software_id' => $software->id,
                'device_id'          => $validated['device_id'],
            ],
            [
                'status' => $software->default_status,
            ]
        );

        // Build update payload — always refresh last_check_date
        $updates = ['last_check_date' => now()];

        // Update env fingerprint fields if provided
        $optionalFields = [
            'user_name', 'user_domain', 'machine_name', 'os_version',
            'framework_version', 'current_directory', 'current_culture', 'current_ui_culture',
        ];
        foreach ($optionalFields as $field) {
            if (isset($validated[$field]) && $validated[$field] !== null) {
                $updates[$field] = $validated[$field];
            }
        }

        // Boolean fields need special handling
        foreach (['is_64bit_os', 'is_64bit_process'] as $boolField) {
            if ($request->has($boolField)) {
                $updates[$boolField] = $request->boolean($boolField);
            }
        }

        // Only save if something actually changed (avoids unnecessary DB writes)
        $device->fill($updates);
        if ($device->isDirty()) {
            $device->save();
        }

        // Return the device status — client software acts on this
        return response()->json([
            'status' => $device->status,
        ]);
    }
}
