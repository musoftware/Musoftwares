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

        // Master Kill Switch: If software is not active as a whole, deny access to all devices
        if (! ($software->is_active ?? true)) {
            return response()->json([
                'status'               => SerialDevice::STATUS_INACTIVE,
                'is_active'            => false,
                'software_disabled'    => true,
                'has_linked_user'      => false,
                'has_active_license'   => false,
                'is_expired'           => false,
                'expires_at'           => null,
                'custom_keys'          => [],
                'pricing_type'         => $software->pricing_type ?? 'free',
                'billing_cycle'        => $software->billing_cycle ?? 'lifetime',
                'packages'             => [],
                'requires_payment'     => (bool) $software->requires_payment,
                'show_price'           => (bool) ($software->show_price ?? true),
                'show_whatsapp'        => (bool) ($software->show_whatsapp ?? true),
                'price'                => $software->price !== null ? (float) $software->price : null,
                'currency'             => $software->currency ?? 'USD',
                'whatsapp_number'      => $software->whatsapp_number,
                'payment_instructions' => $software->payment_instructions,
                'message'              => 'This software is currently disabled by administrator.',
            ]);
        }

        // Inherit paid settings from alias if this is a newly created software (e.g. Trenz Extract vs WAContactsExtract)
        if (! $softwareExisted && in_array($validated['program_name'], ['Trenz Extract', 'WAContactsExtract'])) {
            $existingPaid = SerialSoftware::whereIn('name', ['WAContactsExtract', 'Trenz Extract'])
                ->where('requires_payment', true)
                ->first();
            if ($existingPaid) {
                $software->update([
                    'requires_payment' => true,
                    'price' => $existingPaid->price,
                    'currency' => $existingPaid->currency,
                    'whatsapp_number' => $existingPaid->whatsapp_number,
                    'payment_instructions' => $existingPaid->payment_instructions,
                    'show_price' => $existingPaid->show_price ?? true,
                    'show_whatsapp' => $existingPaid->show_whatsapp ?? true,
                    'default_status' => SerialSoftware::DEFAULT_STATUS_INACTIVE,
                ]);
            }
        }

        // Auto-create device if first check-in from this machine for this software.
        $deviceExisted = SerialDevice::where('serial_software_id', $software->id)
            ->where('device_id', $validated['device_id'])
            ->exists();

        $initialStatus = $software->requires_payment
            ? SerialDevice::STATUS_INACTIVE
            : $software->default_status;

        $device = $this->findOrCreateDevice($software, $validated['device_id'], $initialStatus);

        // Auto-register device in SerialUserDevice so it immediately appears in Devices management
        $userDevice = \App\Models\SerialUserDevice::withTrashed()->where('device_id', $validated['device_id'])->first();

        if (! $userDevice) {
            $initialAssignmentStatus = $software->requires_payment
                ? \App\Models\SerialUserDevice::STATUS_INACTIVE
                : \App\Models\SerialUserDevice::STATUS_ACTIVE;

            $userDevice = \App\Models\SerialUserDevice::create([
                'device_id' => $validated['device_id'],
                'user_id' => null,
                'status' => $initialAssignmentStatus,
                'notes' => 'Auto-registered: ' . ($validated['machine_name'] ?? $validated['user_name'] ?? 'Device'),
            ]);

            // If an active reseller is allocated to this software, auto-associate the reseller
            $resellerAllocation = \App\Models\SerialSoftwareReseller::where('serial_software_id', $software->id)
                ->where('status', \App\Models\SerialSoftwareReseller::STATUS_ACTIVE)
                ->first();

            if ($resellerAllocation) {
                $userDevice->update(['reseller_id' => $resellerAllocation->user_id]);
            }
        } elseif ($userDevice->trashed()) {
            $userDevice->restore();
        }

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

        $hasLinkedUser = (bool) ($userDevice && $userDevice->user_id && $userDevice->user);
        $isExpired = false;
        $status = $device->status;
        $hasActiveLicense = false;

        // If software requires payment, enforce active license or admin activation verification
        if ($software->requires_payment) {
            if ($userDevice && $userDevice->user_id && \Illuminate\Support\Facades\Schema::hasTable('serial_software_licenses')) {
                $userLicense = \App\Models\SerialSoftwareLicense::where('user_id', $userDevice->user_id)
                    ->where('serial_software_id', $software->id)
                    ->active()
                    ->first();

                if ($userLicense) {
                    $hasActiveLicense = true;
                    if ($userLicense->expires_at && (! $userDevice->expires_at || $userDevice->expires_at != $userLicense->expires_at)) {
                        $userDevice->update(['expires_at' => $userLicense->expires_at]);
                    }
                    if ($device->status !== SerialDevice::STATUS_ACTIVE) {
                        $device->update(['status' => SerialDevice::STATUS_ACTIVE]);
                        $status = SerialDevice::STATUS_ACTIVE;
                    }
                }
            }

            // If the device has been explicitly activated by an admin ($device->status === 'active')
            // or has an active license, it remains active.
            // If it is NOT active and has NO license, check for user temp override.
            if ($device->status !== SerialDevice::STATUS_ACTIVE && ! $hasActiveLicense) {
                $hasTempOverride = $userDevice?->user?->temp_valid_until && now()->lessThanOrEqualTo($userDevice->user->temp_valid_until);
                if ($hasTempOverride) {
                    $status = SerialDevice::STATUS_ACTIVE;
                } else {
                    $status = SerialDevice::STATUS_INACTIVE;
                }
            } else {
                $status = SerialDevice::STATUS_ACTIVE;
            }
        }

        // If device assignment has an expiration date that has passed,
        // override status to inactive so client software safely stops execution.
        if ($userDevice && $userDevice->expires_at) {
            $hasTempOverride = $userDevice->user?->temp_valid_until && now()->lessThanOrEqualTo($userDevice->user->temp_valid_until);
            if (! $hasTempOverride && now()->greaterThan($userDevice->expires_at)) {
                $isExpired = true;
                $status = SerialDevice::STATUS_INACTIVE;
                if ($device->status === SerialDevice::STATUS_ACTIVE) {
                    $device->update(['status' => SerialDevice::STATUS_INACTIVE]);
                }
            }
        }

        $packages = [];
        if ($software->hasPackages()) {
            $packages = $software->packages()->active()->get()->map(fn ($pkg) => [
                'id'            => $pkg->id,
                'name'          => $pkg->name,
                'price'         => (float) $pkg->price,
                'currency'      => $pkg->currency,
                'billing_cycle' => $pkg->billing_cycle,
                'billing_days'  => $pkg->billing_days,
                'description'   => $pkg->description,
                'is_default'    => (bool) $pkg->is_default,
                'custom_values' => $pkg->custom_values ?? [],
            ])->values()->all();
        }

        // Return the device status — client software acts on this.
        return response()->json([
            'status'               => $status,
            'is_active'            => (bool) ($software->is_active ?? true),
            'pricing_type'         => $software->pricing_type ?? ($software->requires_payment ? 'single' : 'free'),
            'billing_cycle'        => $software->billing_cycle ?? 'lifetime',
            'billing_days'         => $software->billing_days,
            'packages'             => $packages,
            'package_id'           => $device->package_id ?? $userDevice?->package_id,
            'has_linked_user'      => $hasLinkedUser,
            'has_active_license'   => $hasActiveLicense,
            'is_expired'           => $isExpired,
            'expires_at'           => $userDevice?->expires_at?->toIso8601String(),
            'custom_keys'          => $device->getResolvedCustomKeys(),
            'requires_payment'     => (bool) $software->requires_payment,
            'show_price'           => (bool) ($software->show_price ?? true),
            'show_whatsapp'        => (bool) ($software->show_whatsapp ?? true),
            'price'                => $software->price !== null ? (float) $software->price : null,
            'currency'             => $software->currency ?? 'USD',
            'whatsapp_number'      => $software->whatsapp_number,
            'payment_instructions' => $software->payment_instructions,
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

        if (! ($software->is_active ?? true)) {
            return response()->json([
                'status'            => SerialDevice::STATUS_INACTIVE,
                'is_active'         => false,
                'software_disabled' => true,
                'message'           => 'This software is currently disabled by administrator.',
            ]);
        }

        $initialStatus = $software->requires_payment ? SerialDevice::STATUS_INACTIVE : SerialDevice::STATUS_ACTIVE;

        $device = $this->findOrCreateDevice($software, $validated['device_id'], $initialStatus);

        // Check if user owns an active license for this software
        $userLicense = \App\Models\SerialSoftwareLicense::where('user_id', $user->id)
            ->where('serial_software_id', $software->id)
            ->active()
            ->first();

        $hasActiveLicense = (bool) $userLicense;

        $targetStatus = (! $software->requires_payment || $hasActiveLicense || $device->status === SerialDevice::STATUS_ACTIVE)
            ? SerialDevice::STATUS_ACTIVE
            : SerialDevice::STATUS_INACTIVE;

        $userDeviceStatus = $targetStatus === SerialDevice::STATUS_ACTIVE
            ? \App\Models\SerialUserDevice::STATUS_ACTIVE
            : \App\Models\SerialUserDevice::STATUS_INACTIVE;

        $expiresAt = $userLicense?->expires_at;

        // Link device to user (handling potential soft deletes cleanly)
        $userDevice = \App\Models\SerialUserDevice::withTrashed()
            ->where('device_id', $validated['device_id'])
            ->first();

        if ($userDevice) {
            if ($userDevice->trashed()) {
                $userDevice->restore();
            }
            $updateData = [
                'user_id' => $user->id,
                'status'  => $userDeviceStatus,
            ];
            if ($expiresAt) {
                $updateData['expires_at'] = $expiresAt;
            }
            $userDevice->update($updateData);
        } else {
            \App\Models\SerialUserDevice::create([
                'device_id'  => $validated['device_id'],
                'user_id'    => $user->id,
                'status'     => $userDeviceStatus,
                'expires_at' => $expiresAt,
            ]);
        }

        // Update device status and check date
        $device->update([
            'status'          => $targetStatus,
            'last_check_date' => now(),
        ]);

        $message = $targetStatus === SerialDevice::STATUS_ACTIVE
            ? 'Device linked and activated successfully.'
            : 'Account linked. Please complete payment to activate your license.';

        $packages = [];
        if ($software->hasPackages()) {
            $packages = $software->packages()->active()->get()->map(fn ($pkg) => [
                'id'            => $pkg->id,
                'name'          => $pkg->name,
                'price'         => (float) $pkg->price,
                'currency'      => $pkg->currency,
                'billing_cycle' => $pkg->billing_cycle,
                'billing_days'  => $pkg->billing_days,
                'description'   => $pkg->description,
                'is_default'    => (bool) $pkg->is_default,
                'custom_values' => $pkg->custom_values ?? [],
            ])->values()->all();
        }

        return response()->json([
            'status'               => $targetStatus,
            'is_active'            => (bool) ($software->is_active ?? true),
            'pricing_type'         => $software->pricing_type ?? ($software->requires_payment ? 'single' : 'free'),
            'billing_cycle'        => $software->billing_cycle ?? 'lifetime',
            'billing_days'         => $software->billing_days,
            'packages'             => $packages,
            'user_exists'          => true,
            'user_name'            => $user->name,
            'requires_payment'     => (bool) $software->requires_payment,
            'show_price'           => (bool) ($software->show_price ?? true),
            'show_whatsapp'        => (bool) ($software->show_whatsapp ?? true),
            'has_active_license'   => $hasActiveLicense,
            'expires_at'           => $expiresAt?->toIso8601String(),
            'price'                => $software->price !== null ? (float) $software->price : null,
            'currency'             => $software->currency ?? 'USD',
            'whatsapp_number'      => $software->whatsapp_number,
            'payment_instructions' => $software->payment_instructions,
            'message'              => $message,
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

        if (! ($software->is_active ?? true)) {
            return response()->json([
                'status'            => SerialDevice::STATUS_INACTIVE,
                'is_active'         => false,
                'software_disabled' => true,
                'message'           => 'This software is currently disabled by administrator.',
            ]);
        }

        $initialStatus = $software->requires_payment ? SerialDevice::STATUS_INACTIVE : SerialDevice::STATUS_ACTIVE;

        $device = $this->findOrCreateDevice($software, $validated['device_id'], $initialStatus);

        // Check if user owns an active license for this software
        $userLicense = \App\Models\SerialSoftwareLicense::where('user_id', $user->id)
            ->where('serial_software_id', $software->id)
            ->active()
            ->first();

        $hasActiveLicense = (bool) $userLicense;

        $targetStatus = (! $software->requires_payment || $hasActiveLicense || $device->status === SerialDevice::STATUS_ACTIVE)
            ? SerialDevice::STATUS_ACTIVE
            : SerialDevice::STATUS_INACTIVE;

        $userDeviceStatus = $targetStatus === SerialDevice::STATUS_ACTIVE
            ? \App\Models\SerialUserDevice::STATUS_ACTIVE
            : \App\Models\SerialUserDevice::STATUS_INACTIVE;

        $expiresAt = $userLicense?->expires_at;

        // Link device to user (handling potential soft deletes cleanly)
        $userDevice = \App\Models\SerialUserDevice::withTrashed()
            ->where('device_id', $validated['device_id'])
            ->first();

        if ($userDevice) {
            if ($userDevice->trashed()) {
                $userDevice->restore();
            }
            $updateData = [
                'user_id' => $user->id,
                'status'  => $userDeviceStatus,
            ];
            if ($expiresAt) {
                $updateData['expires_at'] = $expiresAt;
            }
            $userDevice->update($updateData);
        } else {
            \App\Models\SerialUserDevice::create([
                'device_id'  => $validated['device_id'],
                'user_id'    => $user->id,
                'status'     => $userDeviceStatus,
                'expires_at' => $expiresAt,
            ]);
        }

        // Update device status and check date
        $device->update([
            'status'          => $targetStatus,
            'last_check_date' => now(),
        ]);

        $message = $targetStatus === SerialDevice::STATUS_ACTIVE
            ? 'User registered and device activated successfully.'
            : 'User registered. Please complete payment to activate your license.';

        $packages = [];
        if ($software->hasPackages()) {
            $packages = $software->packages()->active()->get()->map(fn ($pkg) => [
                'id'            => $pkg->id,
                'name'          => $pkg->name,
                'price'         => (float) $pkg->price,
                'currency'      => $pkg->currency,
                'billing_cycle' => $pkg->billing_cycle,
                'billing_days'  => $pkg->billing_days,
                'description'   => $pkg->description,
                'is_default'    => (bool) $pkg->is_default,
                'custom_values' => $pkg->custom_values ?? [],
            ])->values()->all();
        }

        return response()->json([
            'status'               => $targetStatus,
            'is_active'            => (bool) ($software->is_active ?? true),
            'pricing_type'         => $software->pricing_type ?? ($software->requires_payment ? 'single' : 'free'),
            'billing_cycle'        => $software->billing_cycle ?? 'lifetime',
            'billing_days'         => $software->billing_days,
            'packages'             => $packages,
            'user_exists'          => true,
            'user_name'            => $user->name,
            'requires_payment'     => (bool) $software->requires_payment,
            'show_price'           => (bool) ($software->show_price ?? true),
            'show_whatsapp'        => (bool) ($software->show_whatsapp ?? true),
            'price'                => $software->price !== null ? (float) $software->price : null,
            'currency'             => $software->currency ?? 'USD',
            'whatsapp_number'      => $software->whatsapp_number,
            'payment_instructions' => $software->payment_instructions,
            'message'              => $message,
        ]);
    }

    /**
     * Find or create device while properly handling soft-deleted rows.
     */
    private function findOrCreateDevice(SerialSoftware $software, string $deviceId, string $initialStatus): SerialDevice
    {
        $device = SerialDevice::withTrashed()
            ->where('serial_software_id', $software->id)
            ->where('device_id', $deviceId)
            ->first();

        if (! $device) {
            return SerialDevice::create([
                'serial_software_id' => $software->id,
                'device_id'          => $deviceId,
                'status'             => $initialStatus,
            ]);
        }

        if ($device->trashed()) {
            $device->restore();
            $device->update(['status' => $initialStatus]);
        }

        return $device;
    }
}
