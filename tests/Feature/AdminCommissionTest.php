<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Earning;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminCommissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;
    protected User $referredUser;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->currency = Currency::firstOrCreate(
            ['currency' => 'EGP'],
            ['currency' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => 'EGP', 'rate' => 1]
        );

        $this->admin = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => $this->currency->id,
        ]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create([
            'name' => 'Marketer Bob',
            'email' => 'bob@example.com',
            'onboarding_completed' => true,
            'currency_id' => $this->currency->id,
            'user_balance' => 0,
        ]);
        $this->clientUser->assignRole('client');

        $this->referredUser = User::factory()->create([
            'name' => 'Customer Alice',
            'email' => 'alice@example.com',
            'onboarding_completed' => true,
            'currency_id' => $this->currency->id,
        ]);
        $this->referredUser->assignRole('client');
    }

    public function test_admin_can_view_commissions_index_page(): void
    {
        // Create sample earnings
        Earning::create([
            'user_id' => $this->clientUser->id,
            'referred_user_id' => $this->referredUser->id,
            'amount' => 50,
            'currency_id' => $this->currency->id,
            'convert_to_balance_on' => now()->addDays(5)->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/commissions');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Commissions/Index')
            ->has('commissions.data', 1)
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_guest_cannot_access_commissions_index(): void
    {
        $response = $this->get('/admin/commissions');
        $response->assertRedirect('/login');
    }

    public function test_filter_by_search(): void
    {
        Earning::create([
            'user_id' => $this->clientUser->id,
            'referred_user_id' => $this->referredUser->id,
            'amount' => 50,
            'currency_id' => $this->currency->id,
            'convert_to_balance_on' => now()->addDays(5)->toDateString(),
        ]);

        // Search match
        $response = $this->actingAs($this->admin)
            ->get('/admin/commissions?search=Marketer');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('commissions.data', 1)
        );

        // Search no match
        $response = $this->actingAs($this->admin)
            ->get('/admin/commissions?search=NonExistentPerson');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('commissions.data', 0)
        );
    }

    public function test_filter_by_status(): void
    {
        // 1. Pending (future date, no transaction)
        $pending = Earning::create([
            'user_id' => $this->clientUser->id,
            'amount' => 20,
            'currency_id' => $this->currency->id,
            'convert_to_balance_on' => now()->addDays(10)->toDateString(),
        ]);

        // 2. Due (past date, no transaction)
        $due = Earning::create([
            'user_id' => $this->clientUser->id,
            'amount' => 30,
            'currency_id' => $this->currency->id,
            'convert_to_balance_on' => now()->subDays(2)->toDateString(),
        ]);

        // Test status=pending
        $response = $this->actingAs($this->admin)
            ->get('/admin/commissions?status=pending');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('commissions.data', 1)
            ->where('commissions.data.0.id', $pending->id)
        );

        // Test status=due
        $response = $this->actingAs($this->admin)
            ->get('/admin/commissions?status=due');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('commissions.data', 1)
            ->where('commissions.data.0.id', $due->id)
        );
    }

    public function test_admin_can_manually_clear_earning(): void
    {
        $earning = Earning::create([
            'user_id' => $this->clientUser->id,
            'referred_user_id' => $this->referredUser->id,
            'amount' => 75.50,
            'currency_id' => $this->currency->id,
            'convert_to_balance_on' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/commissions/{$earning->id}/clear");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $freshEarning = $earning->fresh();
        $this->assertNotNull($freshEarning->transaction_id);

        $freshUser = $this->clientUser->fresh();
        $this->assertEquals(75.50, (float) $freshUser->user_balance);

        $transaction = Transaction::find($freshEarning->transaction_id);
        $this->assertNotNull($transaction);
        $this->assertEquals('earned', $transaction->type);
        $this->assertEquals(75.50, (float) $transaction->amount);
    }

    public function test_cannot_clear_already_cleared_earning(): void
    {
        $tx = Transaction::create([
            'user_id' => $this->clientUser->id,
            'amount' => 50,
            'type' => 'earned',
            'currency_id' => $this->currency->id,
        ]);

        $earning = Earning::create([
            'user_id' => $this->clientUser->id,
            'amount' => 50,
            'currency_id' => $this->currency->id,
            'transaction_id' => $tx->id,
            'convert_to_balance_on' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/commissions/{$earning->id}/clear");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
