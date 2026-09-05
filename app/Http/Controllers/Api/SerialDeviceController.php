<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Serial Device Check-In API — called by client software on startup.
 *
 * Flow:
 * 1. Software sends program_name + device_id + optional env info.
 * 2. SerialSoftware auto-created if new program_name.
 * 3. SerialDevice auto-created with software's default_status if new device.
 * 4. If device already exists, env info is updated + last_check_date refreshed.
 * 5. Response: { "status": "active" } or { "status": "inactive" }.
 * 6. Software reads status and enables/disables accordingly.
 *
 * Security & Access:
 *  - ══════════════════════════════════════════════════════════════════════════════
 *  - 🚨 CRITICAL SYSTEM NOTICE - DO NOT MODIFY OR ADD COMPLEX SECURITY 🚨
 *  - 🔴 تحذير هام جداً: سيستم الـ Serial حساس لأقصى درجة! ممنوع إضافة أي حمايات معقدة أو توثيق نهائياً.
 *  - 🔴 CRITICAL: NO COMPLEX PROTECTION / HMAC / AUTH ALLOWED HERE.
 *  - Public endpoint called by all C# programs/devices on startup.
 *  - Throttled 60 req/min per IP via `throttle:60,1`.
 *  - ══════════════════════════════════════════════════════════════════════════════
 */
class SerialDeviceController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_name' => ['required', 'string', 'max:255'],
            'device_id' => ['required', 'string', 'max:255'],
            'user_name' => ['nullable', 'string', 'max:255'],
            'user_domain' => ['nullable', 'string', 'max:255'],
            'machine_name' => ['nullable', 'string', 'max:255'],
            'os_version' => ['nullable', 'string', 'max:255'],
            'framework_version' => ['nullable', 'string', 'max:255'],
            'current_directory' => ['nullable', 'string'],
            'current_culture' => ['nullable', 'string', 'max:100'],
            'current_ui_culture' => ['nullable', 'string', 'max:100'],
        ]);

        // Track first-time-seen software as a notable audit event. The admin
        // console does not expect unknown programs to start checking in.
        $softwareExisted = SerialSoftware::where('name', $validated['program_name'])->exists();

        $software = SerialSoftware::firstOrCreate(
            ['name' => $validated['program_name']],
            ['default_status' => SerialSoftware::DEFAULT_STATUS_ACTIVE]
        );

        // Auto-create device if first check-in from this machine for this software.
        $deviceExisted = SerialDevice::where('serial_software_id', $software->id)
            ->where('device_id', $validated['device_id'])
            ->exists();

        $device = SerialDevice::firstOrCreate(
            [
                'serial_software_id' => $software->id,
                'device_id' => $validated['device_id'],
            ],
            [
                'status' => $software->default_status,
            ]
        );

        // Build update payload — always refresh last_check_date.
        $updates = ['last_check_date' => now()];

        // Update env fingerprint fields if provided.
        $optionalFields = [
            'user_name', 'user_domain', 'machine_name', 'os_version',
            'framework_version', 'current_directory', 'current_culture', 'current_ui_culture',
        ];
        foreach ($optionalFields as $field) {
            if (isset($validated[$field]) && $validated[$field] !== null) {
                $updates[$field] = $validated[$field];
            }
        }

        // Boolean fields need special handling.
        foreach (['is_64bit_os', 'is_64bit_process'] as $boolField) {
            if ($request->has($boolField)) {
                $updates[$boolField] = $request->boolean($boolField);
            }
        }

        // Only save if something actually changed (avoids unnecessary DB writes).
        $device->fill($updates);
        if ($device->isDirty()) {
            $device->save();
        }

        // Check if device is linked to a user
        $hasLinkedUser = \App\Models\SerialUserDevice::where('device_id', $validated['device_id'])
            ->whereNotNull('user_id')
            ->whereHas('user')
            ->exists();

        // Return the device status — client software acts on this.
        return response()->json([
            'status'          => $device->status,
            'has_linked_user' => (bool) $hasLinkedUser,
        ]);
    }

    /**
     * Check if user exists by email and link device if found.
     * Called when client enters email in the connection/activation dialog.
     */
    public function lookupOrLinkUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_name' => ['required', 'string', 'max:255'],
            'device_id'    => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = \App\Models\User::whereRaw('LOWER(email) = ?', [$email])->first();

        // If user not found, tell client to display registration dialog
        if (! $user) {
            return response()->json([
                'status'      => 'not_found',
                'user_exists' => false,
                'message'     => 'User with this email was not found.',
            ]);
        }

        // Ensure software & device records exist
        $software = SerialSoftware::firstOrCreate(
            ['name' => $validated['program_name']],
            ['default_status' => SerialSoftware::DEFAULT_STATUS_ACTIVE]
        );

        $device = SerialDevice::firstOrCreate(
            [
                'serial_software_id' => $software->id,
                'device_id'          => $validated['device_id'],
            ],
            [
                'status' => SerialDevice::STATUS_ACTIVE,
            ]
        );

        // Link device to user (handling potential soft deletes cleanly)
        $userDevice = \App\Models\SerialUserDevice::withTrashed()
            ->where('device_id', $validated['device_id'])
            ->first();

        if ($userDevice) {
            if ($userDevice->trashed()) {
                $userDevice->restore();
            }
            $userDevice->update([
                'user_id' => $user->id,
                'status'  => \App\Models\SerialUserDevice::STATUS_ACTIVE,
            ]);
        } else {
            \App\Models\SerialUserDevice::create([
                'device_id' => $validated['device_id'],
                'user_id'   => $user->id,
                'status'    => \App\Models\SerialUserDevice::STATUS_ACTIVE,
            ]);
        }

        // Ensure device status is active
        $device->update([
            'status'          => SerialDevice::STATUS_ACTIVE,
            'last_check_date' => now(),
        ]);

        return response()->json([
            'status'      => SerialDevice::STATUS_ACTIVE,
            'user_exists' => true,
            'user_name'   => $user->name,
            'message'     => 'Device linked successfully to user account.',
        ]);
    }

    /**
     * Register a new user and link the device immediately.
     * Called when client fills in the new user dialog (Name, Phone, Country Code, Email).
     */
    public function registerUserAndDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_name' => ['required', 'string', 'max:255'],
            'device_id'    => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255'],
            'name'         => ['required', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'country_code' => ['nullable', 'string', 'max:10'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = \App\Models\User::whereRaw('LOWER(email) = ?', [$email])->first();

        // If user already exists, update info if provided
        if (! $user) {
            $user = new \App\Models\User();
            $user->name = trim($validated['name']);
            $user->email = $email;
            $user->password = \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16));
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'phone')) {
                $user->phone = $validated['phone'] ?? null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'mobile_1')) {
                $user->mobile_1 = $validated['phone'] ?? null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'whatsapp_number')) {
                $user->whatsapp_number = $validated['phone'] ?? null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'country')) {
                $user->country = $validated['country_code'] ?? null;
            }
            $user->save();

            // Assign client role if Spatie roles available
            try {
                if (method_exists($user, 'syncRoles')) {
                    $user->syncRoles(['client']);
                } elseif (method_exists($user, 'assignRole')) {
                    $user->assignRole('client');
                }
            } catch (\Throwable $e) {
                // Ignore role assignment failure if roles table not initialized
            }
        }

        // Ensure software & device records exist
        $software = SerialSoftware::firstOrCreate(
            ['name' => $validated['program_name']],
            ['default_status' => SerialSoftware::DEFAULT_STATUS_ACTIVE]
        );

        $device = SerialDevice::firstOrCreate(
            [
                'serial_software_id' => $software->id,
                'device_id'          => $validated['device_id'],
            ],
            [
                'status' => SerialDevice::STATUS_ACTIVE,
            ]
        );

        // Link device to user (handling potential soft deletes cleanly)
        $userDevice = \App\Models\SerialUserDevice::withTrashed()
            ->where('device_id', $validated['device_id'])
            ->first();

        if ($userDevice) {
            if ($userDevice->trashed()) {
                $userDevice->restore();
            }
            $userDevice->update([
                'user_id' => $user->id,
                'status'  => \App\Models\SerialUserDevice::STATUS_ACTIVE,
            ]);
        } else {
            \App\Models\SerialUserDevice::create([
                'device_id' => $validated['device_id'],
                'user_id'   => $user->id,
                'status'    => \App\Models\SerialUserDevice::STATUS_ACTIVE,
            ]);
        }

        // Ensure device status is active
        $device->update([
            'status'          => SerialDevice::STATUS_ACTIVE,
            'last_check_date' => now(),
        ]);

        return response()->json([
            'status'      => SerialDevice::STATUS_ACTIVE,
            'user_exists' => true,
            'user_name'   => $user->name,
            'message'     => 'User registered and device activated successfully.',
        ]);
    }
}
