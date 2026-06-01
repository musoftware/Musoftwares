<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserReferralRequestWithdraw;
use App\Models\UserPaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWithdrawRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function createAdmin()
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        return $admin;
    }

    private function createClient()
    {
        $client = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');
        return $client;
    }

    public function test_admin_can_access_withdraw_requests_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.withdraw-requests.index'));

        $response->assertSuccessful();
    }

    public function test_non_admin_cannot_access_withdraw_requests_index()
    {
        $client = $this->createClient();

        $response = $this->actingAs($client)->get(route('admin.withdraw-requests.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_withdraw_request_and_it_changes_to_reviewing()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();

        $paymentMethod = UserPaymentMethod::forceCreate([
            'user_id' => $client->id,
            'status' => 'active',
            'mobile' => '01000000000',
            'bank_name' => 'Test Bank',
            'type' => 'bank',
            'bank_number' => '123456789',
            'currency_id' => \App\Models\Currency::first()->id ?? 1
        ]);

        $withdrawRequest = UserReferralRequestWithdraw::forceCreate([
            'user_id' => $client->id,
            'user_payment_method_id' => $paymentMethod->id,
            'amount' => 50,
            'status' => 'pending',
            'currency_id' => \App\Models\Currency::first()->id ?? 1,
            'payment_method' => 'Bank',
            'payment_info' => 'Test info'
        ]);

        $response = $this->actingAs($admin)->get(route('admin.withdraw-requests.show', $withdrawRequest->id));

        $response->assertSuccessful();

        $this->assertDatabaseHas('user_referral_request_withdraws', [
            'id' => $withdrawRequest->id,
            'status' => 'reviewing',
        ]);
    }

    public function test_admin_can_update_withdraw_request_status()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();

        $paymentMethod = UserPaymentMethod::forceCreate([
            'user_id' => $client->id,
            'status' => 'active',
            'mobile' => '01000000000',
            'bank_name' => 'Test Bank',
            'type' => 'bank',
            'bank_number' => '123456789',
            'currency_id' => \App\Models\Currency::first()->id ?? 1
        ]);

        $withdrawRequest = UserReferralRequestWithdraw::forceCreate([
            'user_id' => $client->id,
            'user_payment_method_id' => $paymentMethod->id,
            'amount' => 50,
            'status' => 'pending',
            'currency_id' => \App\Models\Currency::first()->id ?? 1,
            'payment_method' => 'Bank',
            'payment_info' => 'Test info'
        ]);

        $response = $this->actingAs($admin)->put(route('admin.withdraw-requests.update', $withdrawRequest->id), [
            'status' => 'approved',
        ]);

        $response->assertRedirect(route('admin.withdraw-requests.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_referral_request_withdraws', [
            'id' => $withdrawRequest->id,
            'status' => 'approved',
        ]);
    }
}
