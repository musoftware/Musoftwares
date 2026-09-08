<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLoan;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminUserLoanTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;
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
            'onboarding_completed' => true,
            'currency_id' => $this->currency->id,
            'user_balance' => 0,
        ]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_create_loan_with_type_on_client(): void
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/users/{$this->clientUser->id}/loans", [
                'amount' => 500,
                'currency_id' => $this->currency->id,
                'type' => 'on_client',
                'date' => '2026-09-08',
                'note' => 'Loan to client',
            ]);

        $response->assertRedirect();

        $loan = UserLoan::where('user_id', $this->clientUser->id)->first();
        $this->assertNotNull($loan);
        $this->assertEquals('on_client', $loan->type);
        $this->assertEquals(500, (float) $loan->amount);
        $this->assertTrue($loan->isOnClient());
        $this->assertFalse($loan->isOnBusiness());
    }

    public function test_admin_can_create_loan_with_type_on_business(): void
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/users/{$this->clientUser->id}/loans", [
                'amount' => 1000,
                'currency_id' => $this->currency->id,
                'type' => 'on_business',
                'date' => '2026-09-08',
                'note' => 'Loan from client to business',
            ]);

        $response->assertRedirect();

        $loan = UserLoan::where('user_id', $this->clientUser->id)->first();
        $this->assertNotNull($loan);
        $this->assertEquals('on_business', $loan->type);
        $this->assertEquals(1000, (float) $loan->amount);
        $this->assertTrue($loan->isOnBusiness());
        $this->assertFalse($loan->isOnClient());
    }

    public function test_admin_can_recharge_client_balance_from_business_loan(): void
    {
        $loan = $this->clientUser->loans()->create([
            'amount' => 1000,
            'paid_amount' => 0,
            'currency_id' => $this->currency->id,
            'type' => 'on_business',
            'status' => 'active',
            'date' => '2026-09-08',
            'note' => 'Business owes client',
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/users/{$this->clientUser->id}/loans/{$loan->id}/recharge-balance", [
                'amount' => 400,
                'date' => '2026-09-08',
                'note' => 'Initial recharge for project',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check loan was updated
        $freshLoan = $loan->fresh();
        $this->assertEquals(400, (float) $freshLoan->paid_amount);
        $this->assertEquals('active', $freshLoan->status);

        // Check loan repayment was created
        $this->assertCount(1, $freshLoan->repayments);
        $this->assertEquals(400, (float) $freshLoan->repayments->first()->amount);

        // Check client balance was recharged
        $freshUser = $this->clientUser->fresh();
        $this->assertEquals(400, (float) $freshUser->user_balance);

        // Check transaction type is received
        $transaction = Transaction::where('user_id', $this->clientUser->id)->latest('id')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('received', $transaction->type);
        $this->assertEquals(400, (float) $transaction->amount);
    }

    public function test_full_recharge_marks_business_loan_as_paid(): void
    {
        $loan = $this->clientUser->loans()->create([
            'amount' => 500,
            'paid_amount' => 0,
            'currency_id' => $this->currency->id,
            'type' => 'on_business',
            'status' => 'active',
            'date' => '2026-09-08',
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/users/{$this->clientUser->id}/loans/{$loan->id}/recharge-balance", [
                'amount' => 500,
                'date' => '2026-09-08',
            ]);

        $response->assertRedirect();

        $freshLoan = $loan->fresh();
        $this->assertEquals(500, (float) $freshLoan->paid_amount);
        $this->assertEquals('paid', $freshLoan->status);

        $freshUser = $this->clientUser->fresh();
        $this->assertEquals(500, (float) $freshUser->user_balance);
    }

    public function test_cannot_recharge_more_than_remaining_loan_amount(): void
    {
        $loan = $this->clientUser->loans()->create([
            'amount' => 500,
            'paid_amount' => 200,
            'currency_id' => $this->currency->id,
            'type' => 'on_business',
            'status' => 'active',
            'date' => '2026-09-08',
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/users/{$this->clientUser->id}/loans/{$loan->id}/recharge-balance", [
                'amount' => 350,
                'date' => '2026-09-08',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $freshLoan = $loan->fresh();
        $this->assertEquals(200, (float) $freshLoan->paid_amount);

        $freshUser = $this->clientUser->fresh();
        $this->assertEquals(0, (float) $freshUser->user_balance);
    }

    public function test_cannot_recharge_from_on_client_loan(): void
    {
        $loan = $this->clientUser->loans()->create([
            'amount' => 500,
            'paid_amount' => 0,
            'currency_id' => $this->currency->id,
            'type' => 'on_client',
            'status' => 'active',
            'date' => '2026-09-08',
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/users/{$this->clientUser->id}/loans/{$loan->id}/recharge-balance", [
                'amount' => 100,
                'date' => '2026-09-08',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $freshUser = $this->clientUser->fresh();
        $this->assertEquals(0, (float) $freshUser->user_balance);
    }
}
