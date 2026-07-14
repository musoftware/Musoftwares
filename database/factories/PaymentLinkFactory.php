<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\PaymentLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentLink>
 */
class PaymentLinkFactory extends Factory
{
    protected $model = PaymentLink::class;

    public function definition(): array
    {
        $currencyId = Currency::query()->first()?->id ?? Currency::create([
            'currency' => 'USD',
            'symbol' => '$',
            'string_format' => '$%s',
        ])->id;

        return [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => User::factory(),
            'client_id' => null,
            'title' => fake()->sentence(3),
            'description' => null,
            'amount' => fake()->randomFloat(2, 10, 1000),
            'currency_id' => $currencyId,
            'status' => PaymentLink::STATUS_PENDING,
            'paid_at' => null,
            'expires_at' => null,
            'cancelled_at' => null,
            'paid_method' => null,
            'paid_transaction_id' => null,
            'metadata' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => PaymentLink::STATUS_PAID,
            'paid_at' => now(),
            'paid_method' => PaymentLink::METHOD_KASHIER,
            'paid_transaction_id' => 'trx_'.fake()->uuid(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => PaymentLink::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDay(),
        ]);
    }
}