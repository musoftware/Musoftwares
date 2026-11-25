<?php

namespace Modules\AppBuilder\Tests\Feature;

use App\Models\AdminSettings;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Tests\TestCase;

/**
 * The admin chat list marks a chat "new" when the customer wrote after this admin last opened it,
 * and gives the server's Cairo date so the page can group chats by day.
 */
class AdminChatNewMarksTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->google()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_a_customer_message_marks_the_chat_new_until_the_admin_opens_it(): void
    {
        $project = $this->chatWithCustomerMessage();
        $this->assertSame([true], $this->newMarks($this->admin));

        $this->actingAs($this->admin)->get("/admin/builder/{$project->uuid}")->assertOk();
        $this->assertSame([false], $this->newMarks($this->admin));

        $this->travel(1)->minutes();
        BuilderMessage::create(['project_id' => $project->id, 'role' => BuilderMessage::USER, 'content' => 'one more thing']);
        $this->assertSame([true], $this->newMarks($this->admin));
    }

    public function test_each_admin_has_their_own_marks(): void
    {
        $project = $this->chatWithCustomerMessage();
        $other = User::factory()->google()->create(['onboarding_completed' => true]);
        $other->assignRole('admin');

        $this->actingAs($this->admin)->get("/admin/builder/{$project->uuid}")->assertOk();
        $this->assertSame([true], $this->newMarks($other));
    }

    public function test_chats_from_before_the_marks_existed_are_not_new(): void
    {
        $this->chatWithCustomerMessage();
        AdminSettings::SetValue('builder_new_since', now()->addMinute()->toDateTimeString());

        $this->assertSame([false], $this->newMarks($this->admin));
    }

    public function test_chats_are_ordered_and_dated_by_their_last_message_not_by_updated_at(): void
    {
        $this->travelTo('2026-11-01 10:00:00 UTC');
        $old = $this->chatWithCustomerMessage();
        $this->travelTo('2026-11-20 10:00:00 UTC');
        $recent = $this->chatWithCustomerMessage();
        // something saved the old chat today, but nobody wrote in it
        $this->travelTo('2026-11-24 00:00:25 UTC');
        $old->forceFill(['awaiting_answer' => true])->save();

        $rows = $this->actingAs($this->admin)->get('/admin/builder')->assertOk()->viewData('page')['props']['projects']['data'];

        $this->assertSame([$recent->id, $old->id], array_column($rows, 'id'));
        $this->assertSame('2026-11-01 12:00:00', $rows[1]['active_at']);
    }

    public function test_the_list_gets_todays_cairo_date(): void
    {
        $this->travelTo('2026-11-25 23:30:00 UTC');

        $this->actingAs($this->admin)->get('/admin/builder')->assertInertia(fn ($page) => $page->where('today', '2026-11-26'));
    }

    private function chatWithCustomerMessage(): BuilderProject
    {
        $project = BuilderProject::create(['uuid' => (string) Str::uuid(), 'status' => BuilderProject::CHATTING, 'currency' => 'USD', 'title' => 'clinic', 'ip' => '41.2.2.2']);
        BuilderMessage::create(['project_id' => $project->id, 'role' => BuilderMessage::USER, 'content' => 'I need a clinic system']);

        return $project;
    }

    private function newMarks(User $admin): array
    {
        return collect($this->actingAs($admin)->get('/admin/builder')->assertOk()->viewData('page')['props']['projects']['data'])->pluck('is_new')->all();
    }
}
