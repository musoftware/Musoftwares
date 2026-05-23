<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SiteSetting;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'app_name', 'value' => 'ModularSaaS', 'group' => 'general'],
            ['key' => 'base_currency', 'value' => 'USD', 'group' => 'currency'],
            ['key' => 'referral_bonus', 'value' => '10.00', 'group' => 'referral'],
            ['key' => 'service_fee_percentage', 'value' => '5.00', 'group' => 'service'],
            ['key' => 'min_withdrawal_amount', 'value' => '50.00', 'group' => 'withdrawal'],
            ['key' => 'points_per_dollar', 'value' => '1', 'group' => 'points'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
