<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\User;
use App\Models\UserEmail;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkCreateUsersTest extends TestCase
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

        // Ensure EGP currency is present
        Currency::firstOrCreate(['currency' => 'EGP'], [
            'symbol' => 'E£',
            'string_format' => 'e£%s',
            'is_default' => false,
        ]);
    }

    public function test_non_admin_cannot_access_bulk_create(): void
    {
        $response = $this->actingAs($this->clientUser)->get('/admin/users/bulk-create');
        $response->assertStatus(403);

        $responsePost = $this->actingAs($this->clientUser)->post('/admin/users/bulk-create', [
            'entries' => 'Test Client, test@example.com',
        ]);
        $responsePost->assertStatus(403);
    }

    public function test_admin_can_access_bulk_create_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users/bulk-create');
        $response->assertStatus(200);
    }

    public function test_bulk_create_processes_various_input_formats(): void
    {
        $input = "Mostafa Kamel, mostafa@example.com\n" . 
                 "AhmedMaher; ahmed@example.com\n" . 
                 "Sami sami@example.com\n" .
                 "InvalidRowNoEmail";

        $response = $this->actingAs($this->admin)->post('/admin/users/bulk-create', [
            'entries' => $input,
        ]);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/BulkCreate')
            ->has('bulk_results', 4)
        );

        // Assert Mostafa Kamel was created
        $this->assertDatabaseHas('users', [
            'email' => 'mostafa@example.com',
            'name' => 'Mostafa Kamel',
        ]);

        // Assert AhmedMaher was camel-split to "Ahmed Maher" and created
        $this->assertDatabaseHas('users', [
            'email' => 'ahmed@example.com',
            'name' => 'Ahmed Maher',
        ]);

        // Assert Sami was processed and appended with "Account"
        $this->assertDatabaseHas('users', [
            'email' => 'sami@example.com',
            'name' => 'Sami Account',
        ]);

        // Assert EGP currency is set correctly
        $egpId = Currency::where('currency', 'EGP')->first()->id;
        $createdUser = User::where('email', 'mostafa@example.com')->first();
        $this->assertEquals($egpId, $createdUser->currency_id);

        // Assert role client is assigned
        $this->assertTrue($createdUser->hasRole('client'));
    }

    public function test_bulk_create_skips_existing_primary_email(): void
    {
        // Pre-create user
        $existing = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing User',
        ]);

        $input = "New Name, existing@example.com";

        $response = $this->actingAs($this->admin)->post('/admin/users/bulk-create', [
            'entries' => $input,
        ]);

        $response->assertStatus(200);
        
        // Assert no new user was created
        $this->assertEquals(1, User::where('email', 'existing@example.com')->count());

        $results = $response->original->getData()['page']['props']['bulk_results'];
        $this->assertEquals('skipped', $results[0]['status']);
        $this->assertStringContainsString('already exists', $results[0]['reason']);
    }

    public function test_bulk_create_skips_existing_alias_email(): void
    {
        // Pre-create user and alias email
        $user = User::factory()->create([
            'email' => 'primary@example.com',
            'name' => 'User With Alias',
        ]);
        UserEmail::create([
            'user_id' => $user->id,
            'email' => 'alias@example.com',
            'verified_at' => now(),
        ]);

        $input = "New User, alias@example.com";

        $response = $this->actingAs($this->admin)->post('/admin/users/bulk-create', [
            'entries' => $input,
        ]);

        $response->assertStatus(200);

        // Assert no user was created with alias@example.com
        $this->assertDatabaseMissing('users', [
            'email' => 'alias@example.com',
        ]);

        $results = $response->original->getData()['page']['props']['bulk_results'];
        $this->assertEquals('skipped', $results[0]['status']);
        $this->assertStringContainsString('already exists', $results[0]['reason']);
    }
}
