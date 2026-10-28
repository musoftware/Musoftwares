<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The admin "create user" form: a wrong entry comes back as a message on its field with the form
 * still filled in, never as a database error page that loses everything typed.
 */
class AdminUserFormTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');
    }

    /** What the create page sends when only the required boxes are filled (empty boxes arrive as ""). */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sara Ahmed', 'full_name' => '', 'email' => 'sara@example.com', 'password' => '',
            'facebook' => '', 'skype' => '', 'phone_number' => '', 'phone_number2' => '', 'whatsapp_number' => '',
            'disable_unpaid_balance_whatsapp' => false, 'job' => '', 'address' => '', 'enable_custom_hour_rate' => false,
            'hour_rate_currency' => '', 'hour_rate' => '', 'booking_rate_currency' => '', 'booking_rate' => '',
            'booking_rate_expires_at' => '', 'salary' => '', 'usd_type' => 'bank_usd', 'currency' => '',
            'subscription_date' => '', 'subscription_plan' => '', 'postpaid_limit' => '', 'subscription_force' => false,
            'client_taxable' => false, 'invoice_taxable' => false, 'timer_taxable' => false, 'allow_referral_system' => false,
            'allow_view_times' => false, 'allow_postpaid' => false, 'enable_notifications' => true, 'kyc_verified' => false,
            'kyc_notes' => '', 'affiliate_commission_percentage' => 1.00, 'add_commission_to_total' => false, 'ref_user_id' => '',
            'slug' => '', 'role' => 'client', 'account_status' => 'active', 'block_reason' => '', 'max_devices' => '',
            'enable_3d_dashboard' => true,
        ], $overrides);
    }

    public function test_a_user_is_created_from_the_form_as_the_browser_sends_it(): void
    {
        $this->actingAs($this->admin)->from('/admin/users/create')->post('/admin/users', $this->form())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'sara@example.com')->firstOrFail();
        $this->assertEquals(12, (float) $user->hour_rate); // the empty box keeps the default rate
        $this->assertTrue($user->hasRole('client'));
    }

    public function test_each_wrong_entry_comes_back_on_its_field(): void
    {
        $existing = User::factory()->create(['email' => 'taken@example.com', 'slug' => 'taken-slug']);

        $cases = [
            'name' => ['name' => 'Sara'],
            'email' => ['email' => 'taken@example.com'],
            'salary' => ['salary' => 'abc'],
            'hour_rate' => ['hour_rate' => '-5'],
            'subscription_date' => ['subscription_date' => '2026-02-31'],
            'booking_rate_expires_at' => ['booking_rate_expires_at' => '31/12/2026'],
            'ref_user_id' => ['ref_user_id' => '999999'],
            'slug' => ['slug' => 'taken-slug'],
            'affiliate_commission_percentage' => ['affiliate_commission_percentage' => '500'],
            'currency' => ['currency' => '999999'],
            'account_status' => ['account_status' => 'suspended'],
        ];

        foreach ($cases as $field => $override) {
            $this->actingAs($this->admin)->from('/admin/users/create')
                ->post('/admin/users', $this->form($override))
                ->assertRedirect('/admin/users/create')
                ->assertSessionHasErrors($field);
        }

        $this->assertSame(0, User::where('email', 'sara@example.com')->count());
        $this->assertSame(1, User::where('slug', 'taken-slug')->count(), $existing->email);
    }

    public function test_messages_use_the_field_names_of_the_page(): void
    {
        app()->setLocale('ar');

        $this->actingAs($this->admin)->from('/admin/users/create')
            ->post('/admin/users?lang=ar', $this->form(['salary' => 'abc', 'name' => 'Sara']));

        $errors = session('errors')->getBag('default');
        $this->assertStringContainsString(__('general.salary_monthly'), $errors->first('salary'));
        $this->assertSame(__('general.name_must_include_a_last_name'), $errors->first('name'));
    }

    public function test_editing_rejects_a_bad_entry_instead_of_failing(): void
    {
        $user = User::factory()->create(['email' => 'edit@example.com']);

        $this->actingAs($this->admin)->from("/admin/users/{$user->id}/edit")
            ->put("/admin/users/{$user->id}", $this->form(['email' => 'edit@example.com', 'ref_user_id' => (string) $user->id]))
            ->assertSessionHasErrors('ref_user_id');

        $this->actingAs($this->admin)->from("/admin/users/{$user->id}/edit")
            ->put("/admin/users/{$user->id}", $this->form(['email' => 'edit@example.com', 'name' => 'Single']))
            ->assertSessionHasNoErrors();
    }
}
