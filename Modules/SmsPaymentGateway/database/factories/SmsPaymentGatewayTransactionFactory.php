<?php

namespace Modules\SmsPaymentGateway\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use App\Models\User;

class SmsPaymentGatewayTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SmsPaymentGatewayTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
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
            'device_id' => \Modules\SmsPaymentGateway\Models\SmsPaymentGatewayDevice::factory(),
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'balance' => $this->faker->randomFloat(2, 1000, 5000),
            'currency_id' => 1,
            'sender' => 'VF-Cash',
            'phone_number' => $this->faker->numerify('010########'),
            'reference_number' => $this->faker->numerify('###########'),
            'sender_name' => $this->faker->name,
            'transaction_date' => now(),
            'sms_message' => 'تم استلام مبلغ',
            'message_id' => $this->faker->uuid,
            'sms_timestamp' => now()->timestamp,
            'status' => 'pending',
            'is_spoofed' => false,
            'spoofing_reason' => null,
            'metadata' => [],
            'is_test' => false,
        ];
    }
}
