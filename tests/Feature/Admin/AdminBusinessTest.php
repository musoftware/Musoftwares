<?php

namespace Tests\Feature\Admin;

use App\Models\AdminSettings;
use App\Models\CostTransaction;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminBusinessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $clientUser;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CurrenciesSeeder::class);

        // Ensure there is at least one currency and set it as business currency
        $this->currency = Currency::first();
        AdminSettings::SetValue('business_currency', (string) $this->currency->id);

        $this->admin = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => $this->currency->id,
        ]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => $this->currency->id,
        ]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_income_trends_with_correct_data(): void
    {
        // 1. Create a received transaction for the current month
        $tReceived = new Transaction;
        $tReceived->user_id = $this->clientUser->id;
        $tReceived->amount = 15000.00;
        $tReceived->type = 'received';
        $tReceived->reason = 'Service Payment';
        $tReceived->currency_id = $this->currency->id;
        $tReceived->created_at = now();
        $tReceived->save();

        // 2. Create a cost transaction for the current month
        $cTransaction = new CostTransaction;
        $cTransaction->reason = 'Hosting';
        $cTransaction->amount = 5000.00;
        $cTransaction->currency_id = $this->currency->id;
        $cTransaction->created_at = now();
        $cTransaction->save();

        // 3. Make request
        $response = $this->actingAs($this->admin)
            ->get(route('admin.income.index'));

        $response->assertStatus(200);

        // 4. Verify Inertia component and trends values
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Business/Income')
            ->has('stats', fn (Assert $stats) => $stats
                ->where('total_received', 15000)
                ->where('total_monthly_income', 15000)
                ->has('monthly_trends')
                ->etc()
            )
        );

        // Verify that the trends array has a non-zero income and expenses value for the current month
        $monthlyTrends = $response->original->getData()['page']['props']['stats']['monthly_trends'];
        $currentMonthName = now()->format('M');

        $currentMonthTrend = collect($monthlyTrends)->firstWhere('name', $currentMonthName);

        $this->assertNotNull($currentMonthTrend);
        $this->assertEquals(15000.00, $currentMonthTrend['income']);
        $this->assertEquals(5000.00, $currentMonthTrend['expenses']);
    }

    public function test_admin_can_view_reports_and_filter_by_client(): void
    {
        $otherClient = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => $this->currency->id,
        ]);
        $otherClient->assignRole('client');

        // Transaction for clientUser
        $t1 = new Transaction;
        $t1->user_id = $this->clientUser->id;
        $t1->amount = 10000.00;
        $t1->type = 'received';
        $t1->reason = 'Client 1 Payment';
        $t1->currency_id = $this->currency->id;
        $t1->created_at = now();
        $t1->save();

        // Transaction for otherClient
        $t2 = new Transaction;
        $t2->user_id = $otherClient->id;
        $t2->amount = 20000.00;
        $t2->type = 'received';
        $t2->reason = 'Client 2 Payment';
        $t2->currency_id = $this->currency->id;
        $t2->created_at = now();
        $t2->save();

        // Request without filter
        $response = $this->actingAs($this->admin)
            ->get(route('admin.business.reports'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Business/Reports')
            ->has('clients')
            ->has('filters')
            ->where('filters.client_id', null)
            ->where('stats.lifetime_income', 30000)
        );

        // Request filtered by clientUser
        $filteredResponse = $this->actingAs($this->admin)
            ->get(route('admin.business.reports', ['client_id' => $this->clientUser->id]));

        $filteredResponse->assertStatus(200);
        $filteredResponse->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Business/Reports')
            ->where('filters.client_id', (string) $this->clientUser->id)
            ->where('stats.lifetime_income', 10000)
        );
    }
}
