<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AdminUserService extends BaseService
{
    /**
     * Create a new platform user from admin panel.
     * Business rule: name must include a last name (space required).
     */
    public function createFromRequest(Request $request): User
    {
        $user = new User;
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
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->mobile_1 = $request->input('mobile_1');
        $user->mobile_2 = $request->input('mobile_2');
        $user->whatsapp_number = $request->input('whatsapp_number');
        $user->telegram_username = $request->input('telegram_username');
        $user->country = $request->input('country');
        $user->city = $request->input('city');
        $user->currency_id = $request->input('currency') ?? $user->currency_id ?? 2;
        $user->max_devices = $request->input('max_devices');

        // Expanded Fields from Edit.jsx
        if ($request->has('full_name')) {
            $user->full_name = $request->input('full_name');
        }
        if ($request->has('facebook')) {
            $user->facebook = $request->input('facebook');
        }
        if ($request->has('skype')) {
            $user->skype = $request->input('skype');
        }
        if ($request->has('phone_number')) {
            $user->phone_number = $request->input('phone_number');
        }
        if ($request->has('phone_number2')) {
            $user->phone_number2 = $request->input('phone_number2');
        }
        if ($request->has('disable_unpaid_balance_whatsapp')) {
            $user->disable_unpaid_balance_whatsapp = $request->boolean('disable_unpaid_balance_whatsapp');
        }
        if ($request->has('job')) {
            $user->job = $request->input('job');
        }
        if ($request->has('address')) {
            $user->address = $request->input('address');
        }

        if ($request->has('hour_rate_currency')) {
            $val = $request->input('hour_rate_currency');
            $user->hour_rate_currency_id = $val ?: ($user->currency_id ?? 1);
        }
        if ($request->has('hour_rate')) {
            $user->hour_rate = $request->input('hour_rate');
        }

        if ($request->has('booking_rate_currency')) {
            $val2 = $request->input('booking_rate_currency');
            $user->booking_rate_currency_id = $val2 ?: null;
        }
        if ($request->has('booking_rate')) {
            $user->booking_rate = $request->input('booking_rate');
        }
        if ($request->has('booking_rate_expires_at')) {
            $user->booking_rate_expires_at = $request->input('booking_rate_expires_at');
        }
        if ($request->has('salary')) {
            $user->salary = $request->input('salary');
        }
        if ($request->has('usd_type')) {
            $user->usd_type = $request->input('usd_type');
        }

        if ($request->has('subscription_date')) {
            $user->subscription_date = $request->input('subscription_date');
        }
        if ($request->has('subscription_plan')) {
            $user->plan_id = $request->input('subscription_plan') ?: null;
        }
        if ($request->has('postpaid_limit')) {
            $val = $request->input('postpaid_limit');
            $user->postpaid_limit = ($val === null || $val === '') ? 100 : $val;
        }
        if ($request->has('subscription_force')) {
            $user->subscription_force = $request->boolean('subscription_force');
        }

        if ($request->has('client_taxable')) {
            $user->client_taxable = $request->boolean('client_taxable');
        }
        if ($request->has('invoice_taxable')) {
            $user->invoice_taxable = $request->boolean('invoice_taxable');
        }
        if ($request->has('timer_taxable')) {
            $user->timer_taxable = $request->boolean('timer_taxable');
        }

        if ($request->has('allow_referral_system')) {
            $user->allow_referral_system = $request->boolean('allow_referral_system');
        }
        if ($request->has('allow_view_times')) {
            $user->allow_view_times = $request->boolean('allow_view_times');
        }
        if ($request->has('allow_postpaid')) {
            $user->allow_postpaid = $request->boolean('allow_postpaid');
        }

        // Referral configuration fields
        if ($request->has('affiliate_commission_percentage')) {
            $user->affiliate_commission_percentage = $request->input('affiliate_commission_percentage');
        }
        if ($request->has('add_commission_to_total')) {
            $user->add_commission_to_total = $request->boolean('add_commission_to_total');
        }
        if ($request->has('ref_user_id')) {
            $user->ref_user_id = $request->input('ref_user_id');
        }
        if ($request->has('slug')) {
            $user->slug = $request->input('slug');
        }

        // Account control
        if ($request->has('account_status')) {
            $user->account_status = $request->input('account_status') ?: 'active';
        }

        if ($request->has('block_reason')) {
            $user->block_reason = $request->input('block_reason') ?: null;
        }

        // KYC management from admin
        $kycVerified = $request->boolean('kyc_verified');
        if ($kycVerified && ! $user->kyc_verified) {
            $user->kyc_verified = true;
            $user->kyc_verified_at = now();
            $user->kyc_verified_by = Auth::id();
        } elseif (! $kycVerified && $user->kyc_verified) {
            $user->kyc_verified = false;
            $user->kyc_verified_at = null;
            $user->kyc_verified_by = null;
        }

        if ($request->has('kyc_notes')) {
            $user->kyc_notes = $request->input('kyc_notes');
        }

        // Capture the pre-save currency BEFORE save(): Model::save() syncs the
        // original attributes to the current ones, so getOriginal() afterwards
        // would return the new value and the change would go undetected.
        $oldCurrencyId = $user->getOriginal('currency_id');

        $user->save();

        if ($user->wasChanged('currency_id')) {
            $oldId = (int) $oldCurrencyId;
            $newId = (int) $user->currency_id;
            if ($oldId > 0 && $oldId !== $newId) {
                app(ClientCurrencyConverterService::class)->convert($user, $oldId, $newId);
            }
        }
    }

    private function applyRoles(User $user, Request $request): void
    {
        if ($request->filled('role')) {
            $roleName = $request->input('role');
            // Ensure the Spatie role exists first so syncRoles does not fail
            Role::findOrCreate($roleName, 'web');
            // Sync a single role (replaces any previous role)
            $user->syncRoles([$roleName]);
            // The role column doesn't exist, we only use Spatie roles.
        }
    }

    public function resetPassword(User $user): string
    {
        $newPassword = Str::random(10);
        $user->password = Hash::make($newPassword);
        $user->save();

        return $newPassword;
    }

    public function toggleBlock(User $user, ?string $reason): void
    {
        if ($user->account_status === 'blocked') {
            $user->account_status = 'active';
            $user->block_reason = null;
        } else {
            $user->account_status = 'blocked';
            $user->block_reason = $reason;
        }

        $user->save();
    }

    public function addTask(User $user, string $title, ?string $description): Task
    {
        return Task::create([
            'user_id' => $user->id,
            'task_name' => $title,
            'task_description' => $description,
        ]);
    }
}
