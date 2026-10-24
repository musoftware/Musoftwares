<?php

namespace Tests\Feature\Admin;

use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyTier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

class AdminTransactionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    protected User $admin;

    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        // This suite writes wallet/cost ledger rows, which convert amounts to the business currency.
        $this->seedUsdEgpRates();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_transactions_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.transactions.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_transactions_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.transactions.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_create_transaction_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.transactions.create', ['user' => $this->clientUser->id]));
        $response->assertStatus(200);
    }

    public function test_create_transaction_page_requires_user(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.transactions.create'));
        $response->assertRedirect(route('admin.users.index'));
    }

    public function test_admin_can_store_transaction(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.transactions.store'), [
            'user' => $this->clientUser->id,
            'type' => 'earned',
            'data' => [
                ['amount' => 100, 'fee' => 0, 'date' => now()->toDateString(), 'reason' => 'Test Reason', 'currency' => 1],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->clientUser->id,
            'type' => 'earned',
        ]);
    }

    public function test_admin_can_store_transaction_with_custom_date_time(): void
    {
        $customDate = '2026-05-15 14:30:00';
        $response = $this->actingAs($this->admin)->post(route('admin.transactions.store'), [
            'user' => $this->clientUser->id,
            'type' => 'earned',
            'data' => [
                [
                    'amount' => 125,
                    'fee' => 10,
                    'reason' => 'Custom Date Test',
                    'currency' => $this->clientUser->currency_id,
                    'created_at' => $customDate,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->clientUser->id,
            'type' => 'earned',
            'amount' => 125,
            'created_at' => $customDate,
        ]);

        $this->assertDatabaseHas('cost_transactions', [
            'user_id' => $this->clientUser->id,
            'amount' => 10,
            'created_at' => $customDate,
        ]);
    }

    private function postReceivedMoney(string $type, array $item): void
    {
        LoyaltyTier::firstOrCreate(['slug' => 'bronze'], ['name' => 'Bronze', 'min_lifetime_points' => 0, 'order_index' => 1]);
        LoyaltyRule::firstOrCreate(['event_type' => 'invoice_payment'], ['name' => 'Invoice payment', 'base_points' => 200, 'is_active' => true]);

        $this->actingAs($this->admin)->post(route('admin.transactions.store'), [
            'user' => $this->clientUser->id,
            'type' => $type,
            'data' => [['amount' => 100, 'fee' => 0, 'reason' => 'Paid in cash', 'currency' => $this->clientUser->currency_id] + $item],
        ])->assertSessionHas('success');
    }

    public function test_received_money_marked_as_used_gives_the_client_invoice_payment_points(): void
    {
        $this->postReceivedMoney('out-timer-received', ['is_used' => '1']);

        $points = LoyaltyPointTransaction::where('user_id', $this->clientUser->id)->where('event_type', 'invoice_payment')->get();

        $this->assertCount(1, $points);
        $expected = (int) LoyaltyRule::forEvent('invoice_payment')->base_points;
        $this->assertSame($expected, (int) $points->first()->points);
        $this->assertSame('marked_as_used', $points->first()->metadata['source']);
        $this->assertGreaterThanOrEqual($expected, (int) $this->clientUser->fresh()->loyalty_points_balance);
    }

    public function test_received_money_not_marked_as_used_gives_no_points(): void
    {
        $this->postReceivedMoney('out-timer-received', ['is_used' => '0']);

        $this->assertDatabaseMissing('loyalty_point_transactions', ['user_id' => $this->clientUser->id, 'event_type' => 'invoice_payment']);
    }

    public function test_only_received_money_earns_points_when_marked_as_used(): void
    {
        $this->postReceivedMoney('earned', ['is_used' => '1']);

        $this->assertDatabaseMissing('loyalty_point_transactions', ['user_id' => $this->clientUser->id, 'event_type' => 'invoice_payment']);
    }
}
