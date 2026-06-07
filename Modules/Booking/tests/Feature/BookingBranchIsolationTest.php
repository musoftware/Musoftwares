<?php

namespace Modules\Booking\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Booking\Models\BookingBranch;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BookingBranchIsolationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_branches_are_isolated_by_tenant()
    {
        $tenant1 = User::forceCreate([
            'name' => 'Tenant1', 
            'email' => 't1@test.com', 
            'password' => 'test',
            ]);
        
        $tenant2 = User::forceCreate([
            'name' => 'Tenant2', 
            'email' => 't2@test.com', 
            'password' => 'test',
            ]);

        $this->actingAs($tenant1);
        $branch1 = BookingBranch::create([
            'tenant_id' => $tenant1->id,
            'name' => 'Downtown Clinic',
            'timezone' => 'UTC'
        ]);

        $this->actingAs($tenant2);
        $branch2 = BookingBranch::create([
            'tenant_id' => $tenant2->id,
            'name' => 'Uptown Clinic',
            'timezone' => 'UTC'
        ]);

        // When acting as tenant 2, should only see Uptown Clinic
        $branches = BookingBranch::all();
        $this->assertCount(1, $branches);
        $this->assertEquals('Uptown Clinic', $branches->first()->name);
        
        // Ensure tenant 2 cannot load tenant 1's branch
        $this->assertNull(BookingBranch::find($branch1->id));
    }
}
