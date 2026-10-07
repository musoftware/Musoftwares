<?php

namespace Tests\Feature\Admin;

use App\Models\AdminSettings;
use App\Models\CostTransaction;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Covers the async user search used instead of sending the full users table,
 * the clamped invoice page size, and the grouped finance trend/forecast queries.
 */
class AdminFinanceListsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CurrenciesSeeder::class);

        $this->currency = Currency::first();
        AdminSettings::SetValue('business_currency', (string) $this->currency->id);

        $this->admin = $this->makeUser('admin', ['name' => 'Zed Admin', 'email' => 'zed-admin@example.com']);
    }

    private function makeUser(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'onboarding_completed' => true,
            'currency_id' => $this->currency->id,
        ], $attributes));
        $user->assignRole($role);

        return $user;
    }

    public function test_user_search_matches_by_name_or_email_and_hides_admins_by_default(): void
    {
        $client = $this->makeUser('client', ['name' => 'Zed Client', 'email' => 'zed-client@example.com']);

        $response = $this->actingAs($this->admin)->getJson(route('admin.users.search', ['q' => 'zed']));

        $response->assertOk()->assertExactJson([
            ['value' => (string) $client->id, 'label' => 'Zed Client (zed-client@example.com)'],
        ]);
    }

    public function test_user_search_can_include_admins(): void
    {
        $this->makeUser('client', ['name' => 'Zed Client', 'email' => 'zed-client@example.com']);

        $response = $this->actingAs($this->admin)->getJson(route('admin.users.search', ['q' => 'zed', 'include_admins' => 1]));

        $response->assertOk()->assertJsonCount(2);
    }

    public function test_user_search_by_id_returns_the_selected_user_for_edit_forms(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.users.search', ['id' => $this->admin->id]));

        $response->assertOk()->assertExactJson([
            ['value' => (string) $this->admin->id, 'label' => 'Zed Admin (zed-admin@example.com)'],
        ]);
    }

    public function test_user_search_returns_at_most_twenty_users(): void
    {
        User::factory()->count(25)->create(['onboarding_completed' => true]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.users.search', ['include_admins' => 1]));

        $response->assertOk()->assertJsonCount(20);
    }

    public function test_user_search_rejects_non_admins_and_bad_input(): void
    {
        $client = $this->makeUser('client');

        $this->actingAs($client)->getJson(route('admin.users.search', ['q' => 'a']))->assertForbidden();
        $this->actingAs($this->admin)->getJson(route('admin.users.search', ['id' => 'abc']))->assertStatus(422);
    }

    public function test_invoice_list_clamps_page_size_and_does_not_send_all_clients(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.invoices.index', ['per_page' => 100000]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Invoices/Index')
            ->where('invoices.per_page', 20)
            ->missing('clients')
        );
    }

    public function test_finance_index_trends_and_forecast_use_grouped_totals(): void
    {
        $client = $this->makeUser('client');
        $this->createTransaction($client, 'received', 1000, now());
        $this->createTransaction($client, 'refunded', 100, now());
        $this->createTransaction($client, 'received', 400, now()->subMonthsNoOverflow(2)->startOfMonth()->addDays(3));
        $this->createCost('Hosting', 200, now());
        $this->createCost('salary', 300, now());

        $invoice = Invoice::forceCreate([
            'user_id' => $client->id,
            'currency_id' => $this->currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
        ]);
        // Set the stored balance directly; saving the model recalculates it from items.
        Invoice::whereKey($invoice->id)->toBase()->update(['unpaid' => 250]);

        $response = $this->actingAs($this->admin)->get(route('admin.finance.index'));
        $response->assertOk();

        $stats = $response->original->getData()['page']['props']['stats'];
        $trends = collect($stats['monthly_trends']);

        $this->assertCount(6, $trends);
        $current = $trends->last();
        $this->assertSame(now()->format('M Y'), $current['month']);
        $this->assertEquals(900, $current['income']);
        $this->assertEquals(200, $current['expenses']);
        $this->assertEquals(300, $current['payroll']);
        $this->assertEquals(400, $current['net_profit']);
        $this->assertEquals(400, $trends->firstWhere('month', now()->subMonthsNoOverflow(2)->format('M Y'))['income']);

        $this->assertEquals(250, $stats['forecast_receivables']['total_outstanding']);
        $this->assertEquals(250, $stats['forecast_receivables']['next_30_days']);
    }

    private function createTransaction(User $user, string $type, float $amount, $createdAt): void
    {
        $transaction = new Transaction;
        $transaction->user_id = $user->id;
        $transaction->amount = $amount;
        $transaction->type = $type;
        $transaction->reason = 'Test '.$type;
        $transaction->currency_id = $this->currency->id;
        $transaction->created_at = $createdAt;
        $transaction->save();
    }

    private function createCost(string $reason, float $amount, $createdAt): void
    {
        $cost = new CostTransaction;
        $cost->reason = $reason;
        $cost->amount = $amount;
        $cost->currency_id = $this->currency->id;
        $cost->created_at = $createdAt;
        $cost->save();
    }
}
