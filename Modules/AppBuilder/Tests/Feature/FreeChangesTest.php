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
    use \Modules\AppBuilder\Tests\SignsInWithGoogle, RefreshDatabase;

    /** @var list<ChatTurn> */
    public array $answers = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInWithGoogle();
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

    public function test_only_a_few_changes_are_free_and_fixes_are_never_counted(): void
    {
        config(['builder.free_changes' => 2]);
        $project = $this->project();

        $this->asks($project, 'build', 'قسم البلس في لوحة التحكم');
        $this->asks($project, 'fix', 'زرار الاشتراك مش بيفتح');
        $this->asks($project, 'build', 'تقرير المبيعات الشهري');
        $state = $this->getJson('/ai/'.$project->uuid.'/state')->assertOk();
        $state->assertJsonPath('requests.warranty.changes.left', 0)->assertJsonPath('requests.warranty.changes.total', 2);
        $this->asks($project, 'build', 'صفحة العروض الأسبوعية');
        $this->asks($project, 'fix', 'لون الزرار غلط في الصفحة الرئيسية');

        $this->assertSame([true, true, true, false, true], $project->requests()->reorder('id')->pluck('free')->all());
        $this->assertGreaterThan(0, (float) $project->requests()->reorder('id')->get()[3]->amount);
    }

    public function test_a_free_change_the_customer_took_back_gives_its_place_back(): void
    {
        config(['builder.free_changes' => 1]);
        $project = $this->project();
        $this->asks($project, 'build', 'قسم البلس في لوحة التحكم');
        $this->assertSame(0, $project->freeChangesLeft());

        $project->requests()->sole()->delete();

        $this->assertSame(1, $project->freeChangesLeft());
    }

    public function test_when_the_free_changes_are_used_paying_the_rest_of_the_estimate_opens_more(): void
    {
        config(['builder.free_changes' => 1]);
        $project = $this->project();
        $project->update(['amount' => 150, 'price_max' => 225]);
        $this->asks($project, 'build', 'قسم البلس في لوحة التحكم');

        $this->asks($project, 'build', 'تقرير المبيعات الشهري');
        $this->asks($project, 'build', 'صفحة العروض الأسبوعية');

        // the new work waits, and one item in the list opens more for the rest up to the top of the estimate
        $pack = $project->requests()->where('kind', 'pack')->sole();
        $this->assertSame('75.00', $pack->amount);
        $this->assertSame(['locked', 'locked'], $project->requests()->where('kind', 'build')->reorder('id')->pluck('status')->skip(1)->values()->all());
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('requests.warranty.changes.pack', '75.00');

        app(\Modules\AppBuilder\Services\ProjectRequests::class)->markPaid($project, [$pack->id], 'TX-PACK', 75);

        // one more free change opened: the first that waited takes it, and nothing is left to pay for another set
        $waited = $project->requests()->where('kind', 'build')->reorder('id')->get();
        $this->assertTrue($waited[1]->free);
        $this->assertSame('done', $pack->refresh()->status);
        $this->assertSame(0.0, $project->refresh()->changesPackPrice());
        $this->assertSame('unpaid', $waited[2]->status);
        $this->assertFalse($waited[2]->free);
    }

    public function test_a_project_with_no_estimate_above_its_price_gets_each_new_change_priced(): void
    {
        config(['builder.free_changes' => 1]);
        $project = $this->project();
        $this->asks($project, 'build', 'قسم البلس في لوحة التحكم');

        $this->asks($project, 'build', 'تقرير المبيعات الشهري');

        $this->assertSame('unpaid', $project->requests()->reorder('id')->get()[1]->status);
        $this->assertFalse($project->requests()->where('kind', 'pack')->exists());
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

    public function test_new_work_priced_before_the_rule_becomes_free_and_a_repeat_of_another_item_leaves(): void
    {
        $project = $this->project();
        $common = ['estimated_tokens' => 40000, 'amount' => 17, 'budget_usd' => 11, 'model' => 'claude-sonnet-5-5', 'status' => 'unpaid', 'kind' => 'build'];
        $plus = $project->requests()->create($common + ['title' => 'قسم إدارة البلس في لوحة التحكم', 'spec' => 'Plus section.']);
        $repeat = $project->requests()->create($common + ['title' => 'زرار التعليق على آخر الأخبار', 'spec' => 'Comments.']);
        $project->requests()->create(['title' => 'تصليح زرار التعليق على آخر الأخبار', 'spec' => 'Comments fix.', 'kind' => 'fix', 'free' => true, 'status' => 'ready', 'amount' => 0, 'model' => 'claude-sonnet-5-5']);
        $other = $project->requests()->create(['title' => 'تطبيق متجر منفصل', 'spec' => 'A shop.', 'kind' => 'new_project'] + $common);

        app(\Modules\AppBuilder\Services\ProjectRequests::class)->freeAllInFirstPeriod();

        $this->assertTrue($plus->refresh()->free);
        $this->assertSame('0.00', $plus->amount);
        $this->assertSoftDeleted($repeat);
        $this->assertFalse($other->refresh()->free);
    }

    public function test_a_customers_picture_has_a_preview_under_the_message(): void
    {
        $project = $this->project();
        $this->answers[] = ChatTurn::fromArray(['reply' => 'تمام', 'quote' => []]);
        $png = \Illuminate\Http\UploadedFile::fake()->createWithContent('plus.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        $state = $this->post('/ai/'.$project->uuid.'/messages', ['text' => 'زي دي', 'files' => [$png]], ['Accept' => 'application/json'])->assertOk()->json();

        $file = collect($state['messages'])->firstWhere('role', 'user')['files'][0];
        $this->assertSame('plus.png', $file['name']);
        $this->get($file['url'])->assertOk()->assertHeader('Content-Type', 'image/png');
    }
}
