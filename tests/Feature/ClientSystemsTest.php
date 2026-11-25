<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * A client's own system licensed by a monthly price and/or a share of its sales: the admin adds it, it checks in
 * with signed requests, gets an Ed25519-signed license, and locks itself while a bill stays unpaid.
 */
class ClientSystemsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $client;

    private Currency $egp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeSecond();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');
        $this->egp = Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP', 'string_format' => '%.2f EGP']);
        $this->client = User::factory()->create(['currency_id' => $this->egp->id, 'onboarding_completed' => true]);
        $this->client->assignRole('client');
    }

    public function test_a_new_system_is_locked_until_its_first_bill_is_paid(): void
    {
        $system = $this->createSystem(['monthly_price' => 500, 'sales_percent' => 0]);

        $bill = $system->bills()->sole();
        $this->assertSame(500.0, (float) $bill->invoice->unpaid);
        $license = $this->license($this->checkIn($system));
        $this->assertSame('locked', $license['state']);
        $this->assertSame(500.0, (float) $license['amount_due']);
        $this->assertStringContainsString('/guest/invoices/'.$bill->invoice_id, $license['pay_url']);

        $bill->invoice->forceFill(['status' => 'paid', 'unpaid' => 0])->save();
        $license = $this->license($this->checkIn($system));
        $this->assertSame('active', $license['state']);
        $this->assertSame(now()->addHours(72)->getTimestamp(), $license['valid_until']);
    }

    public function test_a_share_of_sales_only_system_starts_working_and_bills_its_sales_monthly(): void
    {
        $system = $this->createSystem(['monthly_price' => 0, 'sales_percent' => 10, 'grace_days' => 7]);
        $this->assertNull($system->bills()->sole()->invoice_id);
        $this->assertSame('active', $this->license($this->checkIn($system))['state']);

        $this->signed($system, 'sales', ['sales' => [
            ['id' => 'A-1', 'amount' => 1000, 'currency' => 'EGP', 'sold_at' => now()->addDay()->toIso8601String(), 'status' => 'paid'],
            ['id' => 'A-2', 'amount' => 400, 'currency' => 'EGP', 'sold_at' => now()->addDays(2)->toIso8601String(), 'status' => 'refunded'],
        ]])->assertOk()->assertJson(['accepted' => 2]);

        $this->travel(1)->months();
        $this->assertSame(0, Artisan::call('client-systems:bill'));

        $bill = $system->bills()->latest('period_start')->first();
        $this->assertSame(1000.0, (float) $bill->sales_total);
        $this->assertSame(100.0, (float) $bill->invoice->unpaid);
        $license = $this->license($this->checkIn($system));
        $this->assertSame('due', $license['state']);
        $this->assertSame($bill->lock_at->getTimestamp(), $license['lock_at']);
        $this->assertSame(now()->addHours(72)->getTimestamp(), $license['valid_until']);
        $this->assertNotEmpty($license['message']['ar']);

        $this->travel(8)->days();
        $this->assertSame('locked', $this->license($this->checkIn($system))['state']);
    }

    public function test_a_billed_sale_cannot_change_and_other_currencies_are_refused(): void
    {
        $system = $this->createSystem(['monthly_price' => 0, 'sales_percent' => 5]);
        $sale = ['id' => 'B-1', 'amount' => 200, 'currency' => 'EGP', 'sold_at' => now()->toIso8601String(), 'status' => 'paid'];
        $this->signed($system, 'sales', ['sales' => [$sale]])->assertOk();
        $this->travel(1)->months();
        $this->assertSame(0, Artisan::call('client-systems:bill'));

        $this->signed($system, 'sales', ['sales' => [['amount' => 1] + $sale]])->assertOk()->assertJson(['accepted' => 0, 'already_billed' => ['B-1']]);
        $this->assertSame(200.0, (float) $system->sales()->sole()->amount);
        $this->signed($system, 'sales', ['sales' => [['id' => 'B-2', 'currency' => 'USD'] + $sale]])->assertUnprocessable();
    }

    public function test_unsigned_wrongly_signed_and_old_requests_are_refused(): void
    {
        $system = $this->createSystem(['monthly_price' => 0, 'sales_percent' => 0]);

        $this->postJson('/api/client-systems/check-in', [])->assertUnauthorized();
        $this->signed($system, 'check-in', [], secret: 'wrong')->assertForbidden();
        $this->signed($system, 'check-in', [], timestamp: now()->subMinutes(10)->getTimestamp())->assertUnauthorized();
        $this->assertNull($system->fresh()->last_check_in_at);
    }

    public function test_an_edited_license_module_is_shown_to_the_admin_until_accepted(): void
    {
        $system = $this->createSystem(['monthly_price' => 0, 'sales_percent' => 0]);
        $this->checkIn($system, 'first');
        $this->checkIn($system, 'edited');

        $system->refresh();
        $this->assertSame('first', $system->fingerprint);
        $this->assertSame('edited', $system->pending_fingerprint);
        $this->actingAs($this->admin)->get("/admin/client-systems/{$system->id}")->assertInertia(fn ($page) => $page->whereNot('system.fingerprint_changed_at', null));

        $this->actingAs($this->admin)->post("/admin/client-systems/{$system->id}/accept-fingerprint")->assertRedirect();
        $this->assertSame('edited', $system->fresh()->fingerprint);
        $this->assertNull($system->fresh()->pending_fingerprint);
    }

    public function test_the_admin_can_lock_now_or_keep_it_working_until_a_date(): void
    {
        $system = $this->createSystem(['monthly_price' => 300, 'sales_percent' => 0]);
        // open until tomorrow: the license lasts only until then, not the full offline window
        $this->actingAs($this->admin)->post("/admin/client-systems/{$system->id}/open-until", ['open_until' => now()->addDay()->toDateString()])->assertRedirect();
        $license = $this->license($this->checkIn($system));
        $this->assertSame('active', $license['state']);
        $this->assertSame(now()->addDay()->endOfDay()->getTimestamp(), $license['valid_until']);

        $this->actingAs($this->admin)->post("/admin/client-systems/{$system->id}/toggle-lock")->assertRedirect();
        $this->assertSame('locked', $this->license($this->checkIn($system))['state']);
    }

    public function test_the_prompt_holds_this_systems_keys_and_the_sales_part_only_when_needed(): void
    {
        $monthly = $this->createSystem(['monthly_price' => 100, 'sales_percent' => 0]);
        $shared = $this->createSystem(['name' => 'Shop', 'monthly_price' => 0, 'sales_percent' => 7.5]);

        $this->actingAs($this->admin)->get("/admin/client-systems/{$monthly->id}")->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/ClientSystems/Show')
            ->where('prompt', fn ($prompt) => str_contains($prompt, $monthly->api_key) && str_contains($prompt, $monthly->signing_public_key) && ! str_contains($prompt, '/sales')));
        $this->actingAs($this->admin)->get("/admin/client-systems/{$shared->id}")->assertInertia(fn ($page) => $page
            ->where('prompt', fn ($prompt) => str_contains($prompt, '7.5% of this system') && ! str_contains($prompt, '{{')));
        $this->actingAs($this->admin)->get("/admin/users/{$this->client->id}")->assertInertia(fn ($page) => $page->has('clientSystems', 2));
    }

    public function test_new_keys_stop_the_old_ones(): void
    {
        $system = $this->createSystem(['monthly_price' => 0, 'sales_percent' => 0]);
        $old = clone $system;
        $this->actingAs($this->admin)->post("/admin/client-systems/{$system->id}/rotate-keys")->assertRedirect();

        $this->signed($old, 'check-in', [])->assertForbidden();
        $this->checkIn($system->fresh())->assertOk();
    }

    public function test_only_admins_reach_the_client_system_pages(): void
    {
        $system = $this->createSystem(['monthly_price' => 0, 'sales_percent' => 0]);

        $this->actingAs($this->client)->get("/admin/client-systems/{$system->id}")->assertForbidden();
        $this->actingAs($this->client)->post("/admin/users/{$this->client->id}/client-systems", [])->assertForbidden();
    }

    private function createSystem(array $values): ClientSystem
    {
        $this->actingAs($this->admin)->post("/admin/users/{$this->client->id}/client-systems", $values + [
            'name' => 'Clinic system', 'site_url' => 'https://clinic.example', 'currency_id' => $this->egp->id, 'grace_days' => 7,
        ])->assertRedirect()->assertSessionHasNoErrors();

        return ClientSystem::latest('id')->firstOrFail();
    }

    private function checkIn(ClientSystem $system, ?string $fingerprint = null): TestResponse
    {
        return $this->signed($system->fresh(), 'check-in', ['fingerprint' => $fingerprint, 'version' => '1.0'])->assertOk();
    }

    private function signed(ClientSystem $system, string $path, array $body, ?string $secret = null, ?int $timestamp = null): TestResponse
    {
        $json = json_encode($body);
        $timestamp ??= now()->getTimestamp();

        return $this->call('POST', "/api/client-systems/{$path}", [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SYSTEM_KEY' => $system->api_key,
            'HTTP_X_SYSTEM_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_SYSTEM_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$json, $secret ?? $system->api_secret),
        ], $json);
    }

    /** Verifies the token exactly as the client's system must, and returns its license. */
    private function license(TestResponse $response): array
    {
        [$body, $signature] = explode('.', $response->json('token'));
        $decode = fn (string $part) => base64_decode(strtr($part, '-_', '+/'));
        $system = ClientSystem::where('uuid', json_decode($decode($body), true)['system'])->firstOrFail();
        $this->assertTrue(sodium_crypto_sign_verify_detached($decode($signature), $body, base64_decode($system->signing_public_key)));
        $this->assertFalse(sodium_crypto_sign_verify_detached($decode($signature), $body.'x', base64_decode($system->signing_public_key)));

        return json_decode($decode($body), true);
    }
}
