<?php

namespace Tests\Feature\Financial;

use App\Models\PayoutMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutMethodTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_view_payout_methods_page()
    {
        $response = $this->actingAs($this->user)->get(route('financial.payout-methods.index'));

        $response->assertStatus(200);
        // Inertia assert to ensure the component is rendered
        $response->assertSee('PayoutMethods');
    }

    public function test_can_store_bank_transfer_payout_method()
    {
        $response = $this->actingAs($this->user)->post(route('financial.payout-methods.store'), [
            'type' => 'bank_transfer',
            'details' => [
                'full_name' => 'John Doe',
                'bank_name' => 'Chase Bank',
                'account_number' => '123456789',
                'routing_number' => '987654321',
            ],
            'is_default' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payout_methods', [
            'user_id' => $this->user->id,
            'type' => 'bank_transfer',
            'is_default' => 1,
        ]);

        $method = PayoutMethod::where('user_id', $this->user->id)->first();
        $this->assertEquals('John Doe', $method->details['full_name']);
        $this->assertEquals('Chase Bank', $method->details['bank_name']);
    }

    public function test_fails_to_store_bank_transfer_without_full_name()
    {
        $response = $this->actingAs($this->user)->post(route('financial.payout-methods.store'), [
            'type' => 'bank_transfer',
            'details' => [
                'bank_name' => 'Chase Bank',
                'account_number' => '123456789',
                'routing_number' => '987654321',
            ],
            'is_default' => true,
        ]);

        $response->assertSessionHasErrors(['details.full_name']);
    }

    public function test_can_store_vodafone_cash_payout_method()
    {
        $response = $this->actingAs($this->user)->post(route('financial.payout-methods.store'), [
            'type' => 'vodafone_cash',
            'details' => [
                'mobile_number' => '01012345678',
            ],
            'is_default' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payout_methods', [
            'user_id' => $this->user->id,
            'type' => 'vodafone_cash',
        ]);
    }

    public function test_can_store_instapay_payout_method()
    {
        $response = $this->actingAs($this->user)->post(route('financial.payout-methods.store'), [
            'type' => 'instapay',
            'details' => [
                'instapay_username' => 'johndoe@instapay',
                'mobile_number' => '01012345678',
            ],
            'is_default' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payout_methods', [
            'user_id' => $this->user->id,
            'type' => 'instapay',
        ]);
    }

    public function test_can_update_payout_method()
    {
        $method = PayoutMethod::create([
            'user_id' => $this->user->id,
            'type' => 'bank_transfer',
            'details' => [
                'full_name' => 'John Doe',
                'bank_name' => 'Chase Bank',
                'account_number' => '123456789',
                'routing_number' => '987654321',
            ],
            'is_default' => false,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user)->patch(route('financial.payout-methods.update', $method->id), [
            'type' => 'bank_transfer',
            'details' => [
                'full_name' => 'Jane Doe',
                'bank_name' => 'Bank of America',
                'account_number' => '111222333',
                'routing_number' => '444555666',
            ],
            'is_default' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $method->refresh();
        $this->assertEquals('Jane Doe', $method->details['full_name']);
        $this->assertEquals('Bank of America', $method->details['bank_name']);
        $this->assertEquals(1, $method->is_default);
    }

    public function test_can_delete_payout_method()
    {
        $method = PayoutMethod::create([
            'user_id' => $this->user->id,
            'type' => 'paypal',
            'details' => [
                'paypal_email' => 'test@example.com',
            ],
            'is_default' => false,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user)->delete(route('financial.payout-methods.destroy', $method->id));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertSoftDeleted('payout_methods', [
            'id' => $method->id,
        ]);
    }
}
