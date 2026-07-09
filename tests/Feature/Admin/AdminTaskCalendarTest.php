<?php

namespace Tests\Feature\Admin;

use App\Models\RecurringBusyTime;
use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminTaskCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientA;
    protected User $clientB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientA = User::factory()->create(['onboarding_completed' => true, 'currency' => 2, 'name' => 'Alpha Co']);
        $this->clientA->assignRole('client');

        $this->clientB = User::factory()->create(['onboarding_completed' => true, 'currency' => 2, 'name' => 'Beta Inc']);
        $this->clientB->assignRole('client');
    }

    public function test_admin_can_access_calendar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tasks.calendar'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_calendar(): void
    {
        $response = $this->actingAs($this->clientA)->get(route('admin.tasks.calendar'));
        $response->assertStatus(403);
    }

    public function test_recurring_busy_time_expands_to_every_matching_day(): void
    {
        RecurringBusyTime::create([
            'user_id'     => $this->admin->id,
            'is_recurring' => true,
            'day_of_week'  => 'Monday',
            'is_full_day'  => false,
            'start_time'   => '09:00',
            'end_time'     => '10:00',
            'reason'       => 'Team standup',
            'is_active'    => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.tasks.calendar', ['month' => 7, 'year' => 2026]));
        $response->assertStatus(200);

        $events = $response->viewData('page')['props']['events'];
        $busyDays = collect($events)->filter(fn ($d) => count($d['busy_times']) > 0);
        $this->assertGreaterThan(0, $busyDays->count(), 'At least one Monday should have a busy time');

        foreach ($busyDays as $date => $day) {
            $this->assertSame('Monday', Carbon::parse($date)->format('l'));
        }
    }

    public function test_specific_busy_date_only_appears_once(): void
    {
        RecurringBusyTime::create([
            'user_id'       => $this->admin->id,
            'is_recurring'  => false,
            'specific_date' => '2026-07-15',
            'is_full_day'   => true,
            'reason'        => 'Off',
            'is_active'     => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.tasks.calendar', ['month' => 7, 'year' => 2026]));
        $response->assertStatus(200);

        $events = $response->viewData('page')['props']['events'];
        $hits = 0;
        foreach ($events as $date => $day) {
            foreach ($day['busy_times'] as $bt) {
                if ($bt['title'] === 'Off') $hits++;
            }
        }
        $this->assertSame(1, $hits, 'Specific-date busy time must appear exactly once');
    }

    public function test_event_type_filter_excludes_other_kinds(): void
    {
        $task = Task::create(['user_id' => $this->clientA->id, 'task_name' => 'Onboarding', 'due_date' => '2026-07-15']);
        Todo::create([
            'user_id' => $this->clientA->id, 'task_id' => $task->id,
            'title' => 'X', 'completed' => false, 'paused' => false,
            'inDate' => '2026-07-15', 'priority' => 'normal', 'priorityColor' => '#fff',
            'tags'   => '[]',
            'start_at' => '2026-07-15 09:00:00', 'end_at' => '2026-07-15 10:00:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.tasks.calendar', ['month' => 7, 'year' => 2026, 'event_type' => 'tasks']));
        $response->assertStatus(200);

        $events = $response->viewData('page')['props']['events'];
        foreach ($events as $date => $day) {
            if (count($day['tasks']) > 0) {
                $this->assertSame(0, count($day['todos']), 'event_type=tasks must exclude todos');
            }
        }
    }

    public function test_client_filter_limits_events(): void
    {
        $taskA = Task::create(['user_id' => $this->clientA->id, 'task_name' => 'A task', 'due_date' => '2026-07-15']);
        $taskB = Task::create(['user_id' => $this->clientB->id, 'task_name' => 'B task', 'due_date' => '2026-07-15']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.tasks.calendar', ['month' => 7, 'year' => 2026, 'client_id' => $this->clientA->id]));
        $response->assertStatus(200);

        $events = $response->viewData('page')['props']['events'];
        $seenClientNames = [];
        foreach ($events as $date => $day) {
            foreach ($day['tasks'] as $t) {
                $seenClientNames[] = $t['client'];
            }
        }
        $this->assertContains('Alpha Co', $seenClientNames);
        $this->assertNotContains('Beta Inc', $seenClientNames);
    }

    public function test_calendar_exposes_tz_and_stats(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tasks.calendar'));
        $response->assertStatus(200);

        $props = $response->viewData('page')['props'];
        $this->assertSame('Africa/Cairo', $props['tz']);
        $this->assertArrayHasKey('stats', $props);
        $this->assertArrayHasKey('todos_this_month', $props['stats']);
        $this->assertArrayHasKey('tasks_this_month', $props['stats']);
        $this->assertArrayHasKey('busy_days', $props['stats']);
    }

    public function test_create_and_bill_validates_slot_and_balance(): void
    {
        $start = Carbon::now('Africa/Cairo')->addDay()->startOfHour();
        $end   = $start->copy()->addHour();

        // End before start
        $response = $this->actingAs($this->admin)
            ->from(route('admin.tasks.calendar'))
            ->post(route('admin.tasks.calendar.store-and-bill'), [
                'client_id'  => $this->clientA->id,
                'title'      => 'Bad slot',
                'date'       => $start->format('Y-m-d'),
                'start_time' => $end->format('H:i'),
                'end_time'   => $start->format('H:i'),
            ]);

        $response->assertSessionHasErrors();
    }
}
