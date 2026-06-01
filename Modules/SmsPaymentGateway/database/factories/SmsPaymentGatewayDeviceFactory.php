<?php

namespace Modules\SmsPaymentGateway\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayDevice;
use App\Models\User;

class SmsPaymentGatewayDeviceFactory extends Factory
{
    protected $model = SmsPaymentGatewayDevice::class;

    public function definition()
    {
        $tenantId = \Modules\ERP\Models\Tenant::first()->id ?? \Modules\ERP\Models\Tenant::insertGetId([
            'user_id' => User::factory()->create()->id,
            'name' => 'Test Tenant',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'tenant_id' => $tenantId,
            'user_id' => User::factory(),
            'device_token' => $this->faker->uuid,
            'device_name' => 'Test Device',
            'status' => 'connected',
            'enable_spoof_detection' => true,
        ];
    }
}
