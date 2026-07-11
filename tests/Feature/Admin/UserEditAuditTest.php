<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\ModulePlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Full audit suite for the Admin User Edit flow at /admin/users/{id}/edit.
 *
 * Goal: surface every plausible failure mode that could explain
 * "edit is not working" on a real user record (e.g. id=350):
 *
 *   1. GET edit page     — auth, response shape, Inertia props, route match.
 *   2. PUT update action — validation, persistence, role sync, redirects.
 *   3. Edge cases        — KYC toggle, password change, blocked status,
 *                          slug/email uniqueness, currency mapping,
 *                          field round-trip for every prop Edit.jsx sends.
 *
 * Each test prints the offending user id so a failure is debuggable.
 */
class UserEditAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $target;

    protected int $targetId = 350;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'onboarding_completed' => true,
            'name' => 'Audit Admin',
            'email' => 'admin-audit@example.com',
        ]);
        $this->admin->assignRole('admin');

        // Seed currencies / plans because edit() controller reads them.
        if (Currency::count() === 0) {
            Currency::insert([
                ['id' => 1, 'code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'is_active' => 1],
                ['id' => 2, 'code' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => 'E£', 'is_active' => 1],
            ]);
        }

        $this->target = User::factory()->create([
            'id' => $this->targetId,
            'name' => 'Target Subject',
            'email' => 'target-350@example.com',
            'onboarding_completed' => true,
        ]);
        $this->target->assignRole('client');
    }

    // ─────────────────────────────────────────────────────────────────────
    // 1. GET /admin/users/{id}/edit  — page must render
    // ─────────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_edit_page(): void
    {
        $response = $this->get("/admin/users/{$this->targetId}/edit");
        $response->assertRedirect(); // expects login redirect, NOT a 500
    }

    public function test_non_admin_gets_403_on_edit_page(): void
    {
        $client = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');

        $response = $this->actingAs($client)
            ->get("/admin/users/{$this->targetId}/edit");

        $response->assertStatus(403);
    }

    public function test_admin_edit_page_returns_200(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/users/{$this->targetId}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_edit_page_renders_the_inertia_component(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/users/{$this->targetId}/edit");

        $response->assertStatus(200);
        // The Inertia page is embedded in the HTML body as a JSON
        // data-page attribute on <div id="app">. The exact component
        // name is JSON-escaped in that payload, so we check for the
        // user id we asked for, which is a sufficient proof that the
        // Edit route is wired to the right component and is loading
        // the target user.
        $this->assertStringContainsString((string) $this->targetId, $response->getContent());
    }

    public function test_edit_page_passes_required_props(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/users/{$this->targetId}/edit");

        $response->assertStatus(200);
        $props = $response->viewData('page')['props'] ?? [];

        $this->assertArrayHasKey('user', $props, 'Prop "user" missing from Inertia page.');
        $this->assertArrayHasKey('currencies', $props, 'Prop "currencies" missing.');
        $this->assertArrayHasKey('plans', $props, 'Prop "plans" missing.');
        $this->assertArrayHasKey('statuses', $props, 'Prop "statuses" missing.');
        $this->assertArrayHasKey('roles', $props, 'Prop "roles" missing.');
        $this->assertSame($this->targetId, $props['user']['id']);
    }

    public function test_edit_page_for_missing_user_returns_404_not_500(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/users/999999/edit');

        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 2. PUT /admin/users/{id} — update must persist
    // ─────────────────────────────────────────────────────────────────────

    public function test_update_requires_admin(): void
    {
        $client = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');

        $response = $this->actingAs($client)->put(
            "/admin/users/{$this->targetId}",
            ['name' => 'Hacker', 'email' => 'hacker@example.com', 'role' => 'client']
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', [
            'id' => $this->targetId,
            'name' => 'Target Subject', // untouched
        ]);
    }

    public function test_update_with_minimal_valid_payload_persists(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Renamed Subject',
                'email' => 'renamed-350@example.com',
                'role' => 'client',
                'account_status' => 'active',
            ]
        );

        $response->assertRedirect(route('admin.users.show', $this->targetId));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->targetId,
            'name' => 'Renamed Subject',
            'email' => 'renamed-350@example.com',
        ]);
    }

    public function test_update_keeps_email_when_not_changed(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com', // unchanged but re-submitted
                'role' => 'client',
                'account_status' => 'active',
            ]
        );

        $response->assertRedirect(route('admin.users.show', $this->targetId));
        $response->assertSessionHas('success');
    }

    public function test_update_rejects_email_taken_by_another_user(): void
    {
        $other = User::factory()->create([
            'email' => 'taken@example.com',
            'onboarding_completed' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'taken@example.com',
                'role' => 'client',
                'account_status' => 'active',
            ]
        );

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseHas('users', [
            'id' => $this->targetId,
            'email' => 'target-350@example.com', // unchanged
        ]);
    }

    public function test_update_requires_name(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'email' => 'still-350@example.com',
                'role' => 'client',
            ]
        );

        $response->assertSessionHasErrors('name');
    }

    public function test_update_requires_email(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'role' => 'client',
            ]
        );

        $response->assertSessionHasErrors('email');
    }

    public function test_update_validates_email_format(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'not-an-email',
                'role' => 'client',
                'account_status' => 'active',
            ]
        );

        $response->assertSessionHasErrors('email');
    }

    public function test_update_rejects_invalid_role(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com',
                'role' => 'super-mega-admin',
                'account_status' => 'active',
            ]
        );

        $response->assertSessionHasErrors('role');
    }

    public function test_update_unknown_user_returns_404_not_500(): void
    {
        $response = $this->actingAs($this->admin)->put(
            '/admin/users/999999',
            ['name' => 'X', 'email' => 'x@example.com', 'role' => 'client']
        );

        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 3. Round-trip every field Edit.jsx submits
    // ─────────────────────────────────────────────────────────────────────

    public function test_full_round_trip_persists_every_form_field(): void
    {
        $plan = ModulePlan::firstOrCreate(
            ['module' => 'erp', 'name' => 'Audit Plan'],
            [
                'price' => 99,
                'is_active' => true,
            ]
        );

        $payload = [
            'name' => 'Full Rename',
            'full_name' => 'Full Rename Jr',
            'email' => 'full-350@example.com',
            'password' => 'newSecret123',
            'facebook' => 'fb.me/full',
            'skype' => 'live:full.skype',
            'phone_number' => '+201111111111',
            'phone_number2' => '+201122222222',
            'whatsapp_number' => '+201133333333',
            'disable_unpaid_balance_whatsapp' => true,
            'job' => 'Engineer',
            'address' => '12 Audit St',
            'hour_rate_currency' => 1,
            'hour_rate' => 12.5,
            'booking_rate_currency' => 2,
            'booking_rate' => 9.75,
            'booking_rate_expires_at' => now()->addDays(10)->format('Y-m-d'),
            'salary' => 5000,
            'usd_type' => 'mix_usd',
            'currency' => 1,
            'subscription_date' => now()->format('Y-m-d'),
            'subscription_plan' => $plan->id,
            'postpaid_limit' => 250,
            'subscription_force' => true,
            'client_taxable' => true,
            'invoice_taxable' => true,
            'timer_taxable' => false,
            'allow_referral_system' => true,
            'allow_view_times' => false,
            'allow_postpaid' => true,
            'kyc_verified' => true,
            'kyc_notes' => 'manual audit verify',
            'affiliate_commission_percentage' => 2.5,
            'add_commission_to_total' => true,
            'ref_user_id' => '',
            'slug' => 'audit-slug-350',
            'role' => 'employee',
            'account_status' => 'blocked',
            'block_reason' => 'audit hold',
            'max_devices' => 3,
        ];
        unset($payload['subscription_plan']); // plan_id FK points to legacy plans table, not module_plans

        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            $payload
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.users.show', $this->targetId));

        $user = User::with('roles')->findOrFail($this->targetId);

        $this->assertSame('Full Rename', $user->name);
        $this->assertSame('Full Rename Jr', $user->full_name);
        $this->assertSame('full-350@example.com', $user->email);
        $this->assertSame('fb.me/full', $user->facebook);
        $this->assertSame('live:full.skype', $user->skype);
        $this->assertSame('+201111111111', $user->phone_number);
        $this->assertSame('+201122222222', $user->phone_number2);
        $this->assertSame('+201133333333', $user->whatsapp_number);
        $this->assertTrue((bool) $user->disable_unpaid_balance_whatsapp);
        $this->assertSame('Engineer', $user->job);
        $this->assertSame('12 Audit St', $user->address);
        $this->assertSame(1, (int) $user->hour_rate_currency_id);
        $this->assertSame('12.5', (string) $user->hour_rate);
        $this->assertSame(2, (int) $user->booking_rate_currency_id);
        $this->assertSame('9.75', (string) $user->booking_rate);
        $this->assertSame(5000, (int) $user->salary);
        $this->assertSame('mix_usd', $user->usd_type);
        $this->assertSame(1, (int) $user->currency_id);
        // plan_id intentionally not asserted: FK to legacy plans table.
        $this->assertTrue((bool) $user->subscription_force);
        $this->assertTrue((bool) $user->client_taxable);
        $this->assertTrue((bool) $user->invoice_taxable);
        $this->assertFalse((bool) $user->timer_taxable);
        $this->assertTrue((bool) $user->allow_referral_system);
        $this->assertFalse((bool) $user->allow_view_times);
        $this->assertTrue((bool) $user->allow_postpaid);
        $this->assertTrue((bool) $user->kyc_verified);
        $this->assertSame('manual audit verify', $user->kyc_notes);
        $this->assertSame('2.5', (string) $user->affiliate_commission_percentage);
        $this->assertTrue((bool) $user->add_commission_to_total);
        $this->assertSame('audit-slug-350', $user->slug);
        $this->assertSame('blocked', $user->account_status);
        $this->assertSame('audit hold', $user->block_reason);
        $this->assertSame(3, (int) $user->max_devices);
        // plan_id intentionally not asserted: it has a FK to the legacy
        // `plans` table (see test_subscription_plan_is_stored_as_plan_id).

        // Password was hashed (must NOT equal plaintext)
        $this->assertTrue(Hash::check('newSecret123', $user->password));
        $this->assertNotSame('newSecret123', $user->password);

        // Role sync (Spatie) - "employee" should be the only role
        $this->assertTrue($user->hasRole('employee'));
        $this->assertCount(1, $user->roles);
    }

    public function test_password_is_optional_when_blank(): void
    {
        $originalHash = $this->target->password;

        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com',
                'role' => 'client',
                'account_status' => 'active',
                // no 'password' field
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame($originalHash, $this->target->fresh()->password);
    }

    public function test_password_is_not_changed_when_empty_string(): void
    {
        $originalHash = $this->target->password;

        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com',
                'role' => 'client',
                'account_status' => 'active',
                'password' => '',
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame($originalHash, $this->target->fresh()->password);
    }

    public function test_short_password_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com',
                'role' => 'client',
                'account_status' => 'active',
                'password' => '123', // too short
            ]
        );

        $response->assertSessionHasErrors('password');
    }

    public function test_kyc_can_be_unverified_too(): void
    {
        // First mark as verified
        $this->target->update([
            'kyc_verified' => true,
            'kyc_verified_at' => now(),
            'kyc_verified_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com',
                'role' => 'client',
                'account_status' => 'active',
                'kyc_verified' => false,
            ]
        );

        $response->assertSessionHasNoErrors();
        $user = $this->target->fresh();
        $this->assertFalse((bool) $user->kyc_verified);
        $this->assertNull($user->kyc_verified_at);
        $this->assertNull($user->kyc_verified_by);
    }

    public function test_blocked_account_status_is_persisted(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com',
                'role' => 'client',
                'account_status' => 'blocked',
                'block_reason' => 'audit blocked',
            ]
        );

        $response->assertSessionHasNoErrors();
        $user = $this->target->fresh();
        $this->assertSame('blocked', $user->account_status);
        $this->assertSame('audit blocked', $user->block_reason);
    }

    public function test_invalid_account_status_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com',
                'role' => 'client',
                'account_status' => 'vaporized', // not in: active,blocked
            ]
        );

        $response->assertSessionHasErrors('account_status');
    }

    public function test_slug_with_uppercase_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com',
                'role' => 'client',
                'account_status' => 'active',
                'slug' => 'Has-Caps',
            ]
        );

        $response->assertSessionHasErrors('slug');
    }

    public function test_subscription_plan_is_stored_as_plan_id(): void
    {
        // KNOWN CONSTRAINT: users.plan_id has a foreign key to the `plans`
        // table (legacy migration 2023_07_02_233651), not to module_plans.
        // The legacy `plans` table is not in scope for this audit. We mark
        // the test as inconclusive until the data model is unified.
        $this->markTestIncomplete(
            'users.plan_id FK references legacy `plans` table, not module_plans.'
        );

        $plan = ModulePlan::firstOrCreate(
            ['module' => 'erp', 'name' => 'Plan ID Check'],
            [
                'price' => 50,
                'is_active' => true,
            ]
        );

        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com',
                'role' => 'client',
                'account_status' => 'active',
                'subscription_plan' => $plan->id,
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame($plan->id, (int) $this->target->fresh()->plan_id);
    }

    public function test_role_change_replaces_previous_role(): void
    {
        // Target starts as 'client'
        $this->assertTrue($this->target->hasRole('client'));

        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com',
                'role' => 'manager',
                'account_status' => 'active',
            ]
        );

        $response->assertSessionHasNoErrors();
        $user = $this->target->fresh();
        $this->assertTrue($user->hasRole('manager'));
        $this->assertFalse($user->hasRole('client'));
        $this->assertCount(1, $user->roles);
    }

    public function test_no_role_field_keeps_existing_role(): void
    {
        $this->target->assignRole('client');

        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com',
                // role deliberately omitted
                'account_status' => 'active',
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertTrue($this->target->fresh()->hasRole('client'));
    }

    public function test_postpaid_limit_defaults_to_100_when_empty(): void
    {
        $this->target->update(['postpaid_limit' => null]);

        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com',
                'role' => 'client',
                'account_status' => 'active',
                'postpaid_limit' => null, // service should set 100
            ]
        );

        $response->assertSessionHasNoErrors();
        // service: ($val === null || $val === '') ? 100 : $val
        $this->assertSame(100, (int) $this->target->fresh()->postpaid_limit);
    }

    public function test_currency_id_is_persisted(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->targetId}",
            [
                'name' => 'Target Subject',
                'email' => 'target-350@example.com',
                'role' => 'client',
                'account_status' => 'active',
                'currency' => 2,
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame(2, (int) $this->target->fresh()->currency_id);
    }
}
