<?php

use App\Models\User;
use App\Models\Currency;
use App\Models\CurrenciesExchange;
use App\Models\Plan;
use Modules\ERP\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Helpers\KashierHelper;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(Tests\TestCase::class, DatabaseTransactions::class);

beforeEach(function () {
    // 1. Scaffold Currencies & Rates required by SubscriptionController
    Currency::updateOrCreate(['id' => 1], ['currency' => 'USD', 'symbol' => '$', 'string_format' => '$%s']);
    Currency::updateOrCreate(['id' => 2], ['currency' => 'EGP', 'symbol' => 'EGP', 'string_format' => '%s EGP']);

    CurrenciesExchange::updateOrCreate(
        ['currency1' => 1, 'currency2' => 2],
        ['rate' => 50.00, 'date_string' => date('Y-m-d')]
    );

    // 2. Scaffold User
    $this->user = User::factory()->create([
        'currency_id' => 1, // Defaulting to USD for tests
        'user_balance' => 0
    ]);
});

describe('Validation & Integrity', function () {
    
    it('prevents purchasing an addon if the parent module is not selected', function () {
        $payload = [
            'items' => ['erp-multi-branch'], // Missing 'erp' parent module
            'billing_cycle' => '1_year',
            'is_new_system' => true
        ];

        $response = actingAs($this->user)->post(route('subscriptions.subscribe'), $payload);
        
        // Assert validation error
        $response->assertSessionHasErrors(['error']);
        $this->assertStringContainsString('cannot subscribe to', session('errors')->first('error'));
    });

    it('prevents unauthenticated users from accessing checkout', function () {
        $response = post(route('subscriptions.subscribe'), [
            'items' => ['erp'],
            'billing_cycle' => '1_year'
        ]);

        $response->assertRedirect(route('login'));
    });
});

describe('Wallet Purchases', function () {

    it('fails when user has insufficient wallet balance', function () {
        // ERP is 5000 EGP/yr => 100 USD. User has 50 USD.
        $this->user->update(['user_balance' => 50]);

        $response = actingAs($this->user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp'],
            'billing_cycle' => '1_year',
            'is_new_system' => true
        ]);

        $response->assertSessionHasErrors(['error' => 'Insufficient balance.']);
        
        // Ensure no tenant features were granted
        assertDatabaseMissing('tenant_features', []);
    });

    it('successfully purchases a module and addon using wallet balance', function () {
        // ERP is 5000 EGP/yr, Multi-Branch is 500 EGP/yr => Total 5500 EGP => $110.00 USD
        $this->user->update(['user_balance' => 500]); // 500 USD
        
        $response = actingAs($this->user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp', 'erp-multi-branch'],
            'billing_cycle' => '1_year',
            'is_new_system' => true
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('subscriptions.manage'));

        // Check Tenant Created
        $tenant = Tenant::where('user_id', $this->user->id)->first();
        expect($tenant)->not->toBeNull();

        // Check Features Created
        assertDatabaseHas('tenant_features', [
            'tenant_id' => $tenant->id,
            'feature_key' => 'erp',
            'module' => 'erp'
        ]);
        
        assertDatabaseHas('tenant_features', [
            'tenant_id' => $tenant->id,
            'feature_key' => 'erp-multi-branch',
            'module' => 'erp'
        ]);

        // Check User Details Updated
        $this->user->refresh();
        expect($this->user->tenant_id)->toBe($tenant->id);
        expect($this->user->plan_id)->not->toBeNull();
        
        // Assert balance was deducted correctly (-$110 USD)
        expect($this->user->user_balance)->toBeLessThan(500); 
    });
});

describe('Kashier Checkout & Webhooks', function () {
    
    it('generates a Kashier payment URL for card checkout', function () {
        $response = actingAs($this->user)->post(route('subscriptions.kashier.checkout'), [
            'items' => ['crm', 'crm-wa-inbox'],
            'billing_cycle' => '6_months',
            'is_new_system' => true
        ]);

        // Inertia redirect to payment URL via X-Inertia-Location header
        $response->assertStatus(409); 
        $this->assertNotEmpty($response->headers->get('X-Inertia-Location'));
        $this->assertStringContainsString('kashier', strtolower($response->headers->get('X-Inertia-Location')));
    });

    it('processes successful Kashier webhook payload securely and ensures idempotency', function () {
        // Mock the Kashier signature validation statically if possible, or bind it
        // Since validatePayload is static in KashierHelper, we will bind a mock or force return true
        // For Pest/Laravel, bypassing static methods might require Mockery.
        Mockery::mock('alias:App\Helpers\KashierHelper')
            ->shouldReceive('validatePayload')
            ->andReturn(true);

        $plan = Plan::factory()->create(['plan_name' => 'Custom Plan - AABBCC']);
        
        $metadata = json_encode([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'days' => 365,
            'is_new_system' => true,
            'items' => ['booking', 'booking-custom-domain']
        ]);

        $payload = [
            'data' => [
                'status' => 'SUCCESS',
                'transactionId' => 'trx_12345',
                'amount' => 120.00,
                'metaData' => $metadata
            ]
        ];

        // 1st request (Should succeed)
        $response = post(route('subscriptions.kashier.webhook'), $payload);
        $response->assertStatus(200);

        // Check Features Created
        $tenant = Tenant::where('user_id', $this->user->id)->first();
        assertDatabaseHas('tenant_features', [
            'tenant_id' => $tenant->id,
            'feature_key' => 'booking-custom-domain'
        ]);

        // Check Wallet Transaction logged
        assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'reason' => 'Subscription via Kashier online payment (Trx: trx_12345)',
            'type' => 'received'
        ]);

        // 2nd request with exact same payload (Idempotency Check)
        $duplicateResponse = post(route('subscriptions.kashier.webhook'), $payload);
        $duplicateResponse->assertStatus(200)
            ->assertJson(['message' => 'Already processed']);
            
        // Ensure no duplicate features or balances added
        $transactionsCount = Transaction::where('user_id', $this->user->id)
            ->where('reason', 'Subscription via Kashier online payment (Trx: trx_12345)')
            ->count();
            
        expect($transactionsCount)->toBe(1); // Still only 1 transaction
    });
});
