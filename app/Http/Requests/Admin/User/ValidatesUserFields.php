<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Validation\Rule;

/**
 * The fields of the admin user form, checked before anything is written: each rule matches what
 * the users table accepts, so a wrong entry comes back as a message under its field (with the
 * form still filled in) instead of a database error page.
 */
trait ValidatesUserFields
{
    protected function userFieldRules(?int $userId = null): array
    {
        $currency = ['nullable', 'integer', Rule::exists('currencies', 'id')];

        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => 'nullable|in:admin,client,user,employee,manager,moderator,software_reseller',
            'max_devices' => 'nullable|integer|min:0|max:100000',

            'full_name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6|max:255',
            'facebook' => 'nullable|string|max:255',
            'skype' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'phone_number2' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string|max:255',
            'disable_unpaid_balance_whatsapp' => 'nullable|boolean',
            'job' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',

            // ranges follow the column sizes: hour_rate decimal(9,3), salary decimal(8,2), booking_rate decimal(12,2)
            'enable_custom_hour_rate' => 'nullable|boolean',
            'hour_rate_currency' => $currency,
            'hour_rate' => 'nullable|numeric|min:0|max:999999',
            'booking_rate_currency' => $currency,
            'booking_rate' => 'nullable|numeric|min:0|max:9999999999',
            'booking_rate_expires_at' => 'nullable|date_format:Y-m-d',
            'salary' => 'nullable|numeric|min:0|max:999999',
            'usd_type' => 'nullable|in:bank_usd,mix_usd,gold_usd',
            'currency' => $currency,

            'subscription_date' => 'nullable|date_format:Y-m-d',
            'subscription_plan' => ['nullable', 'integer', Rule::exists('module_plans', 'id')],
            'postpaid_limit' => 'nullable|numeric|min:0|max:1000000000',
            'subscription_force' => 'nullable|boolean',

            'client_taxable' => 'nullable|boolean',
            'invoice_taxable' => 'nullable|boolean',
            'timer_taxable' => 'nullable|boolean',

            'allow_referral_system' => 'nullable|boolean',
            'allow_view_times' => 'nullable|boolean',
            'allow_postpaid' => 'nullable|boolean',
            'enable_notifications' => 'nullable|boolean',
            'enable_3d_dashboard' => 'nullable|boolean',

            'kyc_verified' => 'nullable|boolean',
            'kyc_notes' => 'nullable|string|max:5000',

            'affiliate_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'add_commission_to_total' => 'nullable|boolean',
            'ref_user_id' => array_values(array_filter(['nullable', 'integer', Rule::exists('users', 'id'), $userId ? Rule::notIn([$userId]) : null])),
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', Rule::unique('users', 'slug')->ignore($userId)],

            'account_status' => 'nullable|in:active,blocked',
            'block_reason' => 'nullable|string|max:1000',
        ];
    }

    /** Field names in the page language, the same labels the form shows. */
    public function attributes(): array
    {
        return collect([
            'name' => 'name', 'full_name' => 'full_name', 'email' => 'email_address', 'password' => 'password',
            'phone_number' => 'phone_number', 'phone_number2' => 'secondary_phone_number', 'job' => 'job_title',
            'address' => 'address', 'hour_rate' => 'hour_rate', 'hour_rate_currency' => 'hour_rate_currency',
            'booking_rate' => 'booking_rate', 'booking_rate_currency' => 'booking_rate_currency',
            'booking_rate_expires_at' => 'booking_rate_valid_until', 'salary' => 'salary_monthly',
            'currency' => 'client_default_currency', 'subscription_date' => 'subscription_date',
            'subscription_plan' => 'subscription_plan', 'postpaid_limit' => 'postpaid_limit',
            'affiliate_commission_percentage' => 'commission_percentage_percent', 'ref_user_id' => 'referral_user_id',
            'account_status' => 'account_status', 'block_reason' => 'block_reason', 'role' => 'permission',
            'kyc_notes' => 'kyc_admin_notes', 'facebook' => 'facebook', 'skype' => 'skype',
        ])->map(fn (string $key) => __("general.{$key}"))->all();
    }

    protected function requireLastName($validator): void
    {
        $validator->after(function ($validator) {
            $name = trim((string) $this->input('name'));
            if ($name !== '' && ! str_contains($name, ' ')) {
                $validator->errors()->add('name', __('general.name_must_include_a_last_name'));
            }
        });
    }
}
