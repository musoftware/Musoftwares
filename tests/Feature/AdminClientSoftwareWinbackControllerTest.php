<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminClientSoftwareWinbackControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->currency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$']
        );
    }

    public function test_admin_can_view_winback_index(): void
    {
        $inactiveClient = User::factory()->create([
            'email'            => 'client@example.com',
            'last_activity_at' => Carbon::now()->subDays(200),
        ]);

        Invoice::create([
            'user_id'    => $inactiveClient->id,
            'paid'       => 1000,
            'status'     => 'paid',
            'currency'   => $this->currency->id,
            'created_at' => Carbon::now()->subDays(210),
            'updated_at' => Carbon::now()->subDays(210),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/winback');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Winback/Index')
            ->has('clients.data')
            ->has('stats')
            ->has('coupon')
        );
    }

    public function test_admin_can_run_all_campaigns(): void
    {
        Mail::fake();

        $inactiveClient = User::factory()->create([
            'email'            => 'client2@example.com',
            'last_activity_at' => Carbon::now()->subDays(200),
        ]);

        Invoice::create([
            'user_id'    => $inactiveClient->id,
            'paid'       => 1000,
            'status'     => 'paid',
            'currency'   => $this->currency->id,
            'created_at' => Carbon::now()->subDays(210),
            'updated_at' => Carbon::now()->subDays(210),
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/winback/run');

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_send_single_reminder_to_user(): void
    {
        Mail::fake();

        $inactiveClient = User::factory()->create([
            'email'            => 'client3@example.com',
            'last_activity_at' => Carbon::now()->subDays(200),
        ]);

        Invoice::create([
            'user_id'    => $inactiveClient->id,
            'paid'       => 1000,
            'status'     => 'paid',
            'currency'   => $this->currency->id,
            'created_at' => Carbon::now()->subDays(210),
            'updated_at' => Carbon::now()->subDays(210),
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/winback/users/{$inactiveClient->id}/send");

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_preview_email(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/winback/preview?reminder_number=1');

        $response->assertStatus(200);
        $response->assertSee('Musoftwares');
        $response->assertSee('50%');
    }

    public function test_guest_is_redirected_from_winback(): void
    {
        $response = $this->get('/admin/winback');
        $response->assertRedirect('/login');
    }
}
