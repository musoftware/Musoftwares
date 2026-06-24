<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->route('id'))],
            'role'  => 'nullable|in:admin,client,user,employee,manager,moderator',
            'max_devices' => 'nullable|integer|min:0',
            
            // Allow all fields sent by Edit.jsx
            'full_name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
            'facebook' => 'nullable|string|max:255',
            'skype' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'phone_number2' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string|max:255',
            'disable_unpaid_balance_whatsapp' => 'nullable|boolean',
            'job' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            
            'hour_rate_currency' => 'nullable|integer',
            'hour_rate' => 'nullable|numeric|min:0',
            'booking_rate_currency' => 'nullable|integer',
            'booking_rate' => 'nullable|numeric|min:0',
            'booking_rate_expires_at' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'usd_type' => 'nullable|in:bank_usd,mix_usd,gold_usd',
            'currency' => 'nullable|integer',
            
            'subscription_date' => 'nullable|date',
            'subscription_plan' => 'nullable|integer',
            'postpaid_limit' => 'nullable|numeric|min:0',
            'subscription_force' => 'nullable|boolean',
            
            'client_taxable' => 'nullable|boolean',
            'invoice_taxable' => 'nullable|boolean',
            'timer_taxable' => 'nullable|boolean',
            
            'allow_referral_system' => 'nullable|boolean',
            'allow_view_times' => 'nullable|boolean',
            'allow_postpaid' => 'nullable|boolean',
            
            'kyc_verified' => 'nullable|boolean',
            'kyc_notes' => 'nullable|string',
            
            'affiliate_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'add_commission_to_total' => 'nullable|boolean',
            'ref_user_id' => 'nullable|integer',
            'slug' => 'nullable|string|max:100|regex:/^[a-z0-9\-]+$/',
            
            'account_status' => 'nullable|in:active,blocked',
            'block_reason' => 'nullable|string',
        ];
    }
}
