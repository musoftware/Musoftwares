<?php

namespace Modules\AppBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\Chat\ChatModel;
use Modules\AppBuilder\Services\Chat\ChatTurn;
use Tests\TestCase;

/**
 * The first weeks after delivery: every change within the program's main idea is free, new work included; only
 * another program has a price. After them new work is priced, and fixes stay free to the end of the warranty.
 * The same request sent twice stays one item, and a corrected request takes the place of the old one.
 */
class FreeChangesTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<ChatTurn> */
    public array $answers = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['builder.owner_approval' => false, 'builder.warranty_days' => 60, 'builder.free_changes_days' => 45]);
        $this->app->instance(ChatModel::class, new class($this) implements ChatModel
        {
            public function __construct(private FreeChangesTest $test) {}

            public function reply(string $system, array $turns): ChatTurn
            {
                return array_shift($this->test->answers);
            }
        });
    }

    private function project(): BuilderProject
    {
        $project = BuilderProject::create([
            'ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'ZainArena', 'spec' => 'A sports app.', 'project_type' => 'app_full',
            'status' => BuilderProject::READY, 'version' => 1, 'paid_total' => 200, 'built_at' => now(), 'warranty_until' => now()->addDays(60),
        ]);
        $project->messages()->create(['role' => BuilderMessage::ASSISTANT, 'content' => 'تم التسليم.']);
        $this->withSession(['builder.started' => [$project->uuid]]);

        return $project;
    }

    private function asks(BuilderProject $project, string $kind, string $title, int $replaces = 0): void
    {
        $this->answers[] = ChatTurn::fromArray([
            'reply' => 'تمام',
            'replaces_request' => $replaces,
            'quote' => ['ready' => true, 'title' => $title, 'spec' => 'Note: '.$title, 'estimated_output_tokens' => 40000, 'kind' => $kind, 'complex' => false],
        ]);
        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => $title])->assertOk();
    }

    public function test_new_work_within_the_idea_is_free_in_the_first_period_and_another_program_is_not(): void
    {
        $project = $this->project();

        $this->asks($project, 'build', 'قسم البلس في لوحة التحكم');
        $this->asks($project, 'new_project', 'تطبيق متجر منفصل');

        $items = $project->requests()->reorder('id')->get();
        $this->assertTrue($items[0]->free);
        $this->assertFalse($items[1]->free);
        $this->assertGreaterThan(0, (float) $items[1]->amount);
    }

    public function test_after_the_first_period_new_work_is_priced_and_fixes_stay_free(): void
    {
        $project = $this->project();
        $this->travel(46)->days();

        $this->asks($project, 'build', 'قسم البلس في لوحة التحكم');
        $this->asks($project, 'fix', 'زرار الاشتراك مش بيفتح');

        $items = $project->requests()->reorder('id')->get();
        $this->assertFalse($items[0]->free);
        $this->assertTrue($items[1]->free);
    }

    public function test_the_list_never_holds_the_same_request_twice(): void
    {
        $project = $this->project();
        $this->travel(46)->days();

        $this->asks($project, 'build', 'زرار التعليق على آخر الأخبار');
        $old = $project->requests()->sole();
        // "this is a fix, not new work": the corrected item takes the old one's place
        $this->asks($project, 'fix', 'تصليح زرار التعليق على آخر الأخبار', $old->id);
        // "yes, start": the same item again adds nothing
        $this->asks($project, 'fix', 'تصليح زرار التعليق على آخر الأخبار');

        $this->assertSoftDeleted($old);
        $this->assertSame(['تصليح زرار التعليق على آخر الأخبار'], $project->requests()->pluck('title')->all());
    }
}
