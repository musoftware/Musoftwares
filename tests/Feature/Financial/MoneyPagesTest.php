<?php

namespace Tests\Feature\Financial;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

/**
 * The withdrawals and balance pages read wallet.earned_balance, which the controller never sent,
 * so "available to withdraw" always showed 0 and the request button stayed disabled.
 */
class MoneyPagesTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    public function test_withdrawals_and_balance_history_show_what_can_be_withdrawn(): void
    {
        $this->seedUsdEgpRates();
        $user = User::factory()->create(['currency_id' => self::USD, 'pending_commission' => 120, 'onboarding_completed' => true]);

        $this->actingAs($user)->get('/financial/withdrawals')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Client/Financial/Withdrawals')
            ->where('wallet.earned_balance', 120)
        );

        $this->actingAs($user)->get('/financial/transactions')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Client/Financial/Transactions')
            ->where('wallet.earned_balance', 120)
        );
    }

    public function test_money_pages_render(): void
    {
        $this->seedUsdEgpRates();
        $user = User::factory()->create(['currency_id' => self::USD, 'onboarding_completed' => true]);

        foreach ([
            '/financial/add-balance' => 'Client/Financial/AddBalance',
            '/financial/payout-methods' => 'Client/Financial/PayoutMethods',
            '/financial/transfer' => 'Client/Financial/WalletTransfer/Create',
            '/financial/transfer/history' => 'Client/Financial/WalletTransfer/History',
            '/billing/invoices/payment/success' => 'Client/Billing/PaymentResult',
        ] as $url => $component) {
            $this->actingAs($user)->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }
}
