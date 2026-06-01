<?php

namespace Modules\Booking\tests\Unit\Booking\TeamMembers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Booking\app\Features\TeamMembers\Services\TeamMemberManagerService;
use Modules\Booking\app\Features\TeamMembers\Models\BookingTeamMember;
use App\Models\User;

class TeamMemberManagerServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TeamMemberManagerService();
    }

    public function test_it_creates_system_user_and_links_booking_profile()
    {
        $tenantId = 1;

        $data = [
            'tenant_id' => $tenantId,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'job_title' => 'Senior Barber',
            'bio' => '10 years of experience',
            'is_bookable' => true,
        ];

        $profile = $this->service->createTeamMember($data);

        // Assert Profile was created
        $this->assertInstanceOf(BookingTeamMember::class, $profile);
        $this->assertEquals('Senior Barber', $profile->job_title);
        $this->assertEquals(true, $profile->is_bookable);
        
        // Assert System User was created and linked
        $this->assertNotNull($profile->user_id);
        
        $user = User::find($profile->user_id);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals($tenantId, $user->tenant_id);
    }
}
