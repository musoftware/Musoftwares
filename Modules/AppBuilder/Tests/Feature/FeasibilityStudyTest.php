<?php

namespace Modules\AppBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\Chat\ChatModel;
use Modules\AppBuilder\Services\Chat\ChatTurn;
use Modules\AppBuilder\Services\Study\StudyWriter;
use Tests\TestCase;

/** A feasibility study for a serious customer: written after the reply, priced into the quote, safe to show. */
class FeasibilityStudyTest extends TestCase
{
    use RefreshDatabase;

    private function quote(string $type): void
    {
        $turn = ChatTurn::fromArray([
            'reply' => 'تمام، ده السعر',
            'needs_team' => false,
            'quote' => ['ready' => true, 'title' => 'منصة أفلييت', 'spec' => 'Affiliate system.', 'estimated_output_tokens' => 120000, 'kind' => 'build', 'complex' => true, 'design_fee' => 0, 'project_type' => $type],
        ]);
        $this->app->instance(ChatModel::class, new class($turn) implements ChatModel
        {
            public function __construct(private ChatTurn $turn) {}

            public function reply(string $system, array $turns): ChatTurn
            {
                return $this->turn;
            }
        });

        // Claude is the only part replaced; the study it "writes" includes a script that must never reach the page
        $this->app->instance(StudyWriter::class, new class implements StudyWriter
        {
            public function write(string $system, string $brief): array
            {
                return ['html' => '<h2 onclick="x()">الملخص</h2><p>نظام أفلييت.</p><script>alert(1)</script>', 'input_tokens' => 20000, 'output_tokens' => 50000];
            }
        });
    }

    public function test_a_system_gets_a_study_whose_cost_is_part_of_the_price(): void
    {
        $this->quote('system');

        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز سيستم افلييت'])->assertOk()->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();

        // system minimum $150, plus the study: (20k x $4 + 50k x $20) / 1M = $1.08, x3 = $3.24, rounded up to $4
        $this->assertSame('ready', $project->study_status);
        $this->assertSame('4.00', $project->study_fee);
        $this->assertSame('154.00', $project->amount);
        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('quote.study.fee', '4.00')->assertJsonPath('quote.study.status', 'ready');

        $page = $this->get('/ai/'.$uuid.'/study')->assertOk();
        $page->assertSee('الملخص')->assertDontSee('alert(1)', false)->assertDontSee('x()', false);
    }

    public function test_a_study_finished_after_the_payment_never_raises_the_price_paid(): void
    {
        $this->quote('page');
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز صفحة'])->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();
        $paid = (float) $project->amount;
        app(\Modules\AppBuilder\Services\ProjectPayment::class)->markPaidByAdmin($project, 1);

        // the study arrives late, while the paid build waits in the queue
        app(\Modules\AppBuilder\Services\Study\FeasibilityStudy::class)->write($project->refresh());

        $project->refresh();
        $this->assertSame('ready', $project->study_status);
        $this->assertSame($paid, (float) $project->amount);
        $this->assertSame(0.0, (float) $project->study_fee);
        $this->assertFalse($project->hasBalanceDue());
    }

    public function test_a_small_page_gets_no_study(): void
    {
        $this->quote('page');

        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز صفحة'])->json('uuid');

        $this->assertNull(BuilderProject::where('uuid', $uuid)->value('study_status'));
        $this->get('/ai/'.$uuid.'/study')->assertNotFound();
    }
}
