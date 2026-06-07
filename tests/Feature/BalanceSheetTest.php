<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Invoice;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceSheetTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        if (Role::where('name', 'admin')->doesntExist()) {
            Role::create(['name' => 'admin']);
        }

        $this->admin = User::factory()->create([
            'email' => 'admin_bs_test@test.com',
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create([
            'email' => 'client_bs_test@test.com',
            'onboarding_completed' => true,
            'currency_id' => 2,
            'user_balance' => 500,
        ]);
    }

    public function test_balance_sheet_route_exists()
    {
        $response = $this->actingAs($this->admin)->get(
            route('admin.users.balance-sheet', $this->clientUser->id)
        );

        // Should return 200 (PDF stream)
        $response->assertStatus(200);
    }

    public function test_balance_sheet_returns_pdf_content_type()
    {
        $response = $this->actingAs($this->admin)->get(
            route('admin.users.balance-sheet', $this->clientUser->id)
        );

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_balance_sheet_redirects_for_invalid_user()
    {
        $response = $this->actingAs($this->admin)->get(
            route('admin.users.balance-sheet', 999999)
        );

        $response->assertRedirect(route('admin.users.index'));
    }

    public function test_balance_sheet_requires_authentication()
    {
        $response = $this->get(
            route('admin.users.balance-sheet', $this->clientUser->id)
        );

        // Should redirect to login
        $response->assertRedirect();
    }

    public function test_frontend_links_point_to_correct_route()
    {
        // Verify the route generates the correct URL
        $expectedPath = '/admin/users/' . $this->clientUser->id . '/balance-sheet';
        $actualUrl = route('admin.users.balance-sheet', $this->clientUser->id, false);

        $this->assertEquals($expectedPath, $actualUrl);
    }
}
