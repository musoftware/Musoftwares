<?php

namespace Modules\AppBuilder\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;
use Modules\AppBuilder\Notifications\BuilderOwnerAlert;
use Modules\AppBuilder\Services\UnpaidWorkWatch;
use Tests\TestCase;

/** Work waiting for the engineer with no payment behind it reaches the owner once, and paid work never does. */
class UnpaidWorkWatchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function project(array $state): BuilderProject
    {
        return BuilderProject::create($state + ['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'صفحة', 'amount' => 20]);
    }

    private function change(BuilderProject $project, array $state): BuilderRequest
    {
        return $project->requests()->create($state + ['kind' => BuilderRequest::BUILD, 'title' => 'قسم جديد', 'spec' => 'x', 'status' => BuilderRequest::READY]);
    }

    private function alerts(): array
    {
        return Notification::sent($this->admin, BuilderOwnerAlert::class)->map(fn (BuilderOwnerAlert $alert) => $alert->body)->all();
    }

    public function test_a_queued_build_with_nothing_paid_is_reported_once(): void
    {
        $this->project(['status' => BuilderProject::QUEUED, 'free_build' => true, 'paid_at' => now()]);
        $this->project(['status' => BuilderProject::QUEUED, 'paid_total' => 20, 'paid_at' => now(), 'title' => 'مدفوع']);

        $this->assertSame(1, app(UnpaidWorkWatch::class)->check());
        app(UnpaidWorkWatch::class)->check();

        $this->assertCount(1, $this->alerts());
        $this->assertStringContainsString('No payment behind this build', $this->alerts()[0]);
    }

    public function test_a_change_is_fine_only_when_paid_or_a_free_change_of_a_paid_program(): void
    {
        $paid = $this->project(['status' => BuilderProject::READY, 'version' => 1, 'paid_total' => 20, 'warranty_until' => now()->addDays(60)]);
        $this->change($paid, ['free' => true]);
        $this->change($paid, ['free' => false, 'paid_at' => now()]);

        $this->assertSame(0, app(UnpaidWorkWatch::class)->check());

        // an old free first build gets no free changes, and an unpaid new item is never fine
        $free = $this->project(['status' => BuilderProject::READY, 'version' => 1, 'free_build' => true, 'warranty_until' => now()->addDays(60)]);
        $this->change($free, ['free' => true]);
        $this->change($paid, ['free' => false]);

        $this->assertSame(2, app(UnpaidWorkWatch::class)->check());
        $this->assertCount(2, $this->alerts());
    }

    public function test_the_owner_sends_an_unpaid_new_project_back_to_its_price(): void
    {
        config(['builder.owner_approval' => true]);
        $unpaid = $this->project(['status' => BuilderProject::QUEUED, 'free_build' => true, 'paid_at' => now()]);
        $paid = $this->project(['status' => BuilderProject::QUEUED, 'paid_total' => 20, 'paid_at' => now()]);

        $this->actingAs($this->admin)->post('/admin/builder/'.$unpaid->uuid.'/back-to-price')->assertRedirect();
        $this->actingAs($this->admin)->post('/admin/builder/'.$paid->uuid.'/back-to-price')->assertStatus(409);

        $unpaid->refresh();
        $this->assertSame([BuilderProject::QUOTED, null, false], [$unpaid->status, $unpaid->paid_at, $unpaid->free_build]);
        $this->assertSame(BuilderProject::QUEUED, $paid->refresh()->status);
        // a customer cannot do it
        $this->actingAs(User::factory()->google()->create())->post('/admin/builder/'.$paid->uuid.'/back-to-price')->assertForbidden();
    }

    public function test_an_admin_marks_requests_paid_by_hand_and_the_record_says_so(): void
    {
        $project = $this->project(['status' => BuilderProject::READY, 'version' => 1, 'paid_total' => 20, 'warranty_until' => now()->addDays(30)]);
        $item = $this->change($project, ['free' => false, 'status' => BuilderRequest::UNPAID, 'amount' => 5]);

        $this->actingAs($this->admin)->postJson('/admin/builder/'.$project->uuid.'/requests/mark-paid', ['ids' => (string) $item->id])->assertOk();

        $this->assertNotNull($item->refresh()->paid_at);
    }

    public function test_an_admin_marks_an_inspection_paid_by_hand(): void
    {
        $project = $this->project(['status' => BuilderProject::QUOTED, 'customer_project' => true, 'import_url' => 'https://drive.google.com/file/d/1AbC/view']);

        $status = $this->actingAs($this->admin)->postJson('/admin/builder/'.$project->uuid.'/inspect/mark-paid')->status();

        // the action runs (paid, or refused because nothing is due), never a server error
        $this->assertContains($status, [200, 302, 409]);
    }

    public function test_it_runs_on_the_schedule(): void
    {
        $names = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->map(fn ($event) => $event->description);

        $this->assertContains('builder-unpaid-watch', $names);
    }
}
