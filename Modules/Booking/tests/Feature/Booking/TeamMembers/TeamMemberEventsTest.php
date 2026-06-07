<?php

namespace Modules\Booking\tests\Feature\Booking\TeamMembers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use Modules\Booking\app\Features\TeamMembers\Events\TeamMemberAdded;
use Modules\Booking\app\Features\TeamMembers\Events\TeamMemberProfileUpdated;
use Modules\Booking\app\Features\TeamMembers\Models\BookingTeamMember;
use Illuminate\Support\Facades\Notification;
use Modules\Booking\app\Features\TeamMembers\Notifications\WelcomeToTheBookingTeam;

class TeamMemberEventsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_adding_team_member_dispatches_events_and_notifications()
    {
        Event::fake();
        Notification::fake();

        // Create an admin user who is creating the staff
        $admin = User::factory()->create([]);
        $this->actingAs($admin);

        // Assume limits pass or are mocked
        $this->postJson('/api/v1/team-members', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'job_title' => 'Nail Tech',
            'is_bookable' => true,
        ]);

        Event::assertDispatched(TeamMemberAdded::class, function ($event) {
            return $event->teamMember->job_title === 'Nail Tech';
        });

        // Test the notification was sent to the newly created user
        $newUser = User::where('email', 'jane@example.com')->first();
        Notification::assertSentTo($newUser, WelcomeToTheBookingTeam::class);
    }
}
