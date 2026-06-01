<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserReferralRequestWithdraw;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;

class AdminWithdrawRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_withdraw_requests_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.withdraw-requests.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_withdraw_requests_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.withdraw-requests.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_withdraw_request_show(): void
    {
        $paymentMethod = new \App\Models\UserPaymentMethod();
        $paymentMethod->user_id = $this->clientUser->id;
        $paymentMethod->status = 'active';
        $paymentMethod->type = 'bank';
        $paymentMethod->currency_id = 1;
        $paymentMethod->save();

        $withdrawRequest = new UserReferralRequestWithdraw();
        $withdrawRequest->user_id = $this->clientUser->id;
        $withdrawRequest->status = 'pending';
        $withdrawRequest->amount = 100;
        $withdrawRequest->currency_id = 1;
        $withdrawRequest->payment_info = 'Paypal info';
        $withdrawRequest->user_payment_method_id = $paymentMethod->id;
        $withdrawRequest->save();

        $response = $this->actingAs($this->admin)->get(route('admin.withdraw-requests.show', $withdrawRequest));
        
        $response->assertStatus(200);
        $this->assertEquals('reviewing', $withdrawRequest->fresh()->status);
    }

    public function test_admin_can_update_withdraw_request_status(): void
    {
        $paymentMethod = new \App\Models\UserPaymentMethod();
        $paymentMethod->user_id = $this->clientUser->id;
        $paymentMethod->status = 'active';
        $paymentMethod->type = 'bank';
        $paymentMethod->currency_id = 1;
        $paymentMethod->save();

        $withdrawRequest = new UserReferralRequestWithdraw();
        $withdrawRequest->user_id = $this->clientUser->id;
        $withdrawRequest->status = 'pending';
        $withdrawRequest->amount = 100;
        $withdrawRequest->currency_id = 1;
        $withdrawRequest->payment_info = 'Paypal info';
        $withdrawRequest->user_payment_method_id = $paymentMethod->id;
        $withdrawRequest->save();

        $response = $this->actingAs($this->admin)->put(route('admin.withdraw-requests.update', $withdrawRequest), [
            'status' => 'approved'
        ]);

        $response->assertRedirect(route('admin.withdraw-requests.index'));
        $response->assertSessionHas('success');
        $this->assertEquals('approved', $withdrawRequest->fresh()->status);
    }
}
