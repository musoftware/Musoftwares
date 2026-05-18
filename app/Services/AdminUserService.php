<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserService
{
    /**
     * Create a new platform user from admin panel.
     * Business rule: name must include a last name (space required).
     */
    public function createFromRequest(Request $request): User
    {
        $user = new User();
        $user->password = Hash::make($request->filled('password') ? $request->input('password') : Str::random(16));
        $this->applyFields($user, $request);
        $this->applyRoles($user, $request);
        return $user;
    }

    /**
     * Update an existing user from admin request.
     */
    public function updateFromRequest(User $user, Request $request): void
    {
        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }
        $this->applyFields($user, $request);
        $this->applyRoles($user, $request);
    }

    /**
     * Map all editable fields from the request onto the user model and save.
     */
    private function applyFields(User $user, Request $request): void
    {
        $user->name                = $request->input('name');
        $user->email               = $request->input('email');
        $user->phone               = $request->input('phone');
        $user->mobile_1            = $request->input('mobile_1');
        $user->mobile_2            = $request->input('mobile_2');
        $user->whatsapp_number     = $request->input('whatsapp_number');
        $user->telegram_username   = $request->input('telegram_username');
        $user->country             = $request->input('country');
        $user->city                = $request->input('city');
        $user->preferred_currency  = $request->input('preferred_currency') ?? $user->preferred_currency ?? 'USD';

        // Account control
        if ($request->has('account_status')) {
            $user->account_status = $request->input('account_status') ?: 'active';
        }

        // KYC management from admin
        $kycVerified = $request->boolean('kyc_verified');
        if ($kycVerified && !$user->kyc_verified) {
            $user->kyc_verified    = true;
            $user->kyc_verified_at = now();
            $user->kyc_verified_by = Auth::id();
        } elseif (!$kycVerified && $user->kyc_verified) {
            $user->kyc_verified    = false;
            $user->kyc_verified_at = null;
            $user->kyc_verified_by = null;
        }

        if ($request->has('kyc_notes')) {
            $user->kyc_notes = $request->input('kyc_notes');
        }

        $user->save();
    }

    /**
     * Sync the user's Spatie role from the permission field.
     */
    private function applyRoles(User $user, Request $request): void
    {
        if ($request->filled('role')) {
            $roleName = $request->input('role');
            // Sync a single role (replaces any previous role)
            $user->syncRoles([$roleName]);
            // Also keep the role column consistent
            if (in_array($roleName, ['admin', 'client'])) {
                $user->role = $roleName;
                $user->saveQuietly();
            }
        }
    }
}
