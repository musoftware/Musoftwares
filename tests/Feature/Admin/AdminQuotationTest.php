<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;

class AdminQuotationTest extends TestCase
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

    public function test_admin_can_view_quotation_print(): void
    {
        $contract = new Contract();
        $contract->user_id = $this->clientUser->id;
        $contract->total_amount = 100;
        $contract->currency_id = 1;
        $contract->status = 'draft';
        $contract->project_name = 'Test Project';
        $contract->save();

        $response = $this->actingAs($this->admin)->get(route('admin.quotations.print', $contract->id));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_quotation_print(): void
    {
        $contract = new Contract();
        $contract->user_id = $this->clientUser->id;
        $contract->total_amount = 100;
        $contract->currency_id = 1;
        $contract->status = 'draft';
        $contract->project_name = 'Test Project';
        $contract->save();

        $response = $this->actingAs($this->clientUser)->get(route('admin.quotations.print', $contract->id));
        $response->assertStatus(403);
    }
}
