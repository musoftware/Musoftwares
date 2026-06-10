<?php

namespace Tests\Feature\Freelance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Freelance\Models\Skill;
use App\Models\Currency;
use Tests\TestCase;

class FreelanceTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $clientUser;
    protected User $freelancer1;
    protected User $freelancer2;
    protected Skill $skill;
    protected Currency $usdCurrency;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip SaaS module middleware for these isolated feature tests
        $this->withoutMiddleware(\App\Http\Middleware\EnsureSubscriptionIsActive::class);

        // Seed core roles
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Setup base currency
        $this->usdCurrency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['symbol' => '$', 'string_format' => '$%01.2f']
        );

        // Setup Admin User
        $this->adminUser = User::factory()->create([
            'onboarding_completed' => true, 
            'currency_id' => $this->usdCurrency->id
        ]);
        $this->adminUser->assignRole('super_admin');

        // Setup Client User (wealthy)
        $this->clientUser = User::factory()->create([
            'onboarding_completed' => true, 
            'currency_id' => $this->usdCurrency->id, 
            'points_balance' => 1000, 
            'user_balance' => 5000.00
        ]);
        $this->clientUser->assignRole('client');

        // Setup Freelancer 1
        $this->freelancer1 = User::factory()->create([
            'onboarding_completed' => true, 
            'currency_id' => $this->usdCurrency->id, 
            'points_balance' => 100, 
            'user_balance' => 0.00
        ]);
        $this->freelancer1->assignRole('client');

        // Setup Freelancer 2
        $this->freelancer2 = User::factory()->create([
            'onboarding_completed' => true, 
            'currency_id' => $this->usdCurrency->id, 
            'points_balance' => 100, 
            'user_balance' => 0.00
        ]);
        $this->freelancer2->assignRole('client');

        // Setup an approved skill for matching
        $this->skill = Skill::firstOrCreate([
            'name' => 'Laravel',
        ], [
            'description' => 'PHP framework development',
            'status' => 'approved',
        ]);
    }
}
