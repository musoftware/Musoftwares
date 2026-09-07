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
        return [
            'user_id' => User::factory(),
            'device_token' => $this->faker->uuid,
            'device_name' => 'Test Device',
            'status' => 'connected',
            'enable_spoof_detection' => true,
        ];
    }
}
