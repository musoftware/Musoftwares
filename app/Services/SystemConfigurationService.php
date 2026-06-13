<?php

namespace App\Services;

use App\Models\AdminSettings;
use Illuminate\Support\Facades\DB;

class SystemConfigurationService
{
    public function updateSettings(array $settings): void
    {
        // Special case: if business currency changes, we reset calculated values
        if (isset($settings['business_currency']) && AdminSettings::GetValue('business_currency', '') != $settings['business_currency']) {
            DB::table('transactions')->update(['business_calculated' => 0]);
            DB::table('cost_transactions')->update(['business_calculated' => 0]);
        }

        // Loop over supported keys and update them
        $supportedKeys = [
            'business_currency', 'business_name', 'business_phone', 'business_address',
            'business_tax', 'business_email', 'overhead_cost_default', 'ownwallet',
            'payoneer_active', 'paymob_active', 'paymob_token', 'paymob_card_integration',
            'paymob_wallet_integration', 'paymob_card_iframe', 'gumroad',
            'whatsapp_default_channel_id', 'friday_work_allowed',
            'max_devices_per_tenant', 'gemini_api_keys',
            'expected_monthly_income', 'work_days_per_month', 'hours_per_day',
            'google_analytics_id', 'google_tag_manager_id', 'meta_pixel_id',
            'custom_head_scripts', 'custom_body_scripts'
        ];

        foreach ($supportedKeys as $key) {
            if (array_key_exists($key, $settings)) {
                $value = $settings[$key];
                
                // Handle booleans/checkboxes correctly
                if (in_array($key, ['ownwallet', 'payoneer_active', 'paymob_active', 'friday_work_allowed'])) {
                    $value = $value ? '1' : '0';
                }

                AdminSettings::SetValue($key, $value);
            }
        }
    }
}
