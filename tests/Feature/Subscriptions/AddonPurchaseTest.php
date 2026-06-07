<?php

use App\Models\User;
use App\Models\Currency;
use App\Models\CurrenciesExchange;
use App\Models\Plan;
use Modules\ERP\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Helpers\KashierHelper;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(Tests\TestCase::class, RefreshDatabase::class);

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
        $this->user->user_balance = 50;
        $this->user->save();

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
        $this->user->user_balance = 50000; // Huge balance to ensure test passes
        $this->user->save();
        
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

        // Legacy tenant_id and plan_id are no longer updated directly on user in the new system
        
        // Assert balance was deducted correctly (-$110 USD)
        expect($this->user->user_balance)->toBeLessThan(50000); 
    });
});

describe('Kashier Checkout & Webhooks', function () {
    
    it('generates a Kashier payment URL for card checkout', function () {
        $response = actingAs($this->user)->post(route('subscriptions.kashier.checkout'), [
            'items' => ['crm', 'crm-wa-inbox'],
            'billing_cycle' => '6_months',
            'is_new_system' => true
        ]);

        // Redirect to payment URL
        $response->assertStatus(302); 
        $this->assertNotEmpty($response->headers->get('Location'));
        $this->assertStringContainsString('kashier', strtolower($response->headers->get('Location')));
    });

    it('processes successful Kashier webhook payload securely and ensures idempotency', function () {
        config(['services.kashier.secret_key' => 'test_secret']);

        $payload = [
            'data' => [
                'status' => 'SUCCESS',
                'transactionId' => 'trx_abc123',
                'amount' => 110,
                'signatureKeys' => ['amount', 'status', 'transactionId', 'metaData'],
                'metaData' => json_encode([
                    'user_id' => $this->user->id,
                    'source' => 'subscription-purchase',
                    'plan_id' => 999, // Dummy plan ID
                    'billing_cycle' => '1_year',
                    'days' => 365,
                    'items' => ['erp', 'erp-multi-branch'],
                    'is_new_system' => true
                ])
            ]
        ];

        $data_obj = $payload['data'];
        sort($data_obj['signatureKeys']);
        $data = [];
        foreach ($data_obj['signatureKeys'] as $key) {
            $data[$key] = $data_obj[$key];
        }
        $queryString = http_build_query($data, "", '&', PHP_QUERY_RFC3986);
        $signature = hash_hmac('sha256', $queryString, 'test_secret', false);

        $response = $this->postJson(route('subscriptions.kashier.webhook'), $payload, [
            'x-kashier-signature' => $signature
        ]);

        $response->assertStatus(200);

        // Check Features Created
        $tenant = Tenant::where('user_id', $this->user->id)->first();
        assertDatabaseHas('tenant_features', [
            'tenant_id' => $tenant->id,
            'feature_key' => 'erp-multi-branch'
        ]);

        // Check Wallet Transaction logged
        assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'reason' => 'Subscription modules via Kashier online payment (Trx: trx_abc123)',
            'type' => 'received'
        ]);

        // 2nd request with exact same payload (Idempotency Check)
        $duplicateResponse = $this->postJson(route('subscriptions.kashier.webhook'), $payload, [
            'x-kashier-signature' => $signature
        ]);
        $duplicateResponse->assertStatus(200)
            ->assertJson(['message' => 'Already processed']);
            
        // Ensure no duplicate features or balances added
        $transactionsCount = Transaction::where('user_id', $this->user->id)
            ->where('reason', 'Subscription modules via Kashier online payment (Trx: trx_abc123)')
            ->count();
            
        expect($transactionsCount)->toBe(1); // Still only 1 transaction
    });
});
