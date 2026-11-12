<?php

namespace Modules\AppBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\Chat\ChatModel;
use Modules\AppBuilder\Services\Chat\ChatTurn;
use Modules\AppBuilder\Services\Study\FeasibilityStudy;
use Modules\AppBuilder\Services\Study\StudyWriter;
use Tests\TestCase;

/** The feasibility study is bought on its own for a small fixed price, written only then, and comes off the project price. */
class FeasibilityStudyTest extends TestCase
{
    use RefreshDatabase;

    public int $written = 0;

    private function quote(string $type): string
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
        $test = $this;
        $this->app->instance(StudyWriter::class, new class($test) implements StudyWriter
        {
            public function __construct(private FeasibilityStudyTest $test) {}

            public function write(string $system, string $brief): array
            {
                $this->test->written++;

                return ['html' => '<h2 onclick="x()">الملخص</h2><p>نظام أفلييت.</p><script>alert(1)</script>', 'input_tokens' => 20000, 'output_tokens' => 50000];
            }
        });

        return $this->postJson('/ai/messages', ['text' => 'عايز سيستم افلييت'])->assertOk()->json('uuid');
    }

    public function test_a_system_is_offered_the_study_and_nothing_is_written_before_it_is_paid(): void
    {
        $uuid = $this->quote('system');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();

        // system minimum $150; the study is not in the price and costs nothing until bought
        $this->assertSame('150.00', $project->amount);
        $this->assertNull($project->study_status);
        $this->assertSame(0, $this->written);
        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('study.status', 'offer')->assertJsonPath('study.price', '5.00');
        $this->get('/ai/'.$uuid.'/study')->assertNotFound();
    }

    public function test_paying_the_study_writes_it_and_takes_it_off_the_project_price(): void
    {
        $uuid = $this->quote('system');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();

        $this->assertTrue(app(FeasibilityStudy::class)->markPaid($project, 'TX-STUDY'));
        $this->app->terminate();
        // the gateway may report the same payment twice
        $this->assertFalse(app(FeasibilityStudy::class)->markPaid($project->refresh(), 'TX-STUDY'));

        $project->refresh();
        $this->assertSame(1, $this->written);
        $this->assertSame('ready', $project->study_status);
        $this->assertSame('5.00', $project->study_paid);
        $this->assertSame('145.00', $project->amount);
        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('study.status', 'ready')->assertJsonPath('study.credit', '5.00');

        $page = $this->get('/ai/'.$uuid.'/study')->assertOk();
        $page->assertSee('الملخص')->assertDontSee('alert(1)', false)->assertDontSee('x()', false);
    }

    public function test_a_new_quote_after_the_study_still_takes_it_off(): void
    {
        $uuid = $this->quote('system');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();
        app(FeasibilityStudy::class)->markPaid($project, 'TX-STUDY');

        $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'تمام كده'])->assertOk();

        $this->assertSame('145.00', $project->refresh()->amount);
        // bought once: no second offer
        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('study.status', 'ready');
    }

    public function test_the_study_webhook_is_told_apart_from_the_price(): void
    {
        $payment = app(\Modules\AppBuilder\Services\ProjectPayment::class);

        $this->assertSame([12, 0, true], $payment->parseOrderId('bld_12-study_abc'));
        $this->assertSame([12, 3, false], $payment->parseOrderId('bld_12-3_abc'));
        $this->assertNull($payment->parseOrderId('bld_12-x_abc'));
    }

    public function test_a_small_page_gets_no_study(): void
    {
        $uuid = $this->quote('page');

        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('study', null);
        $this->postJson('/ai/'.$uuid.'/study/pay', ['client_name' => 'Ali', 'client_email' => 'a@example.com', 'client_whatsapp' => '01000000000'])->assertStatus(409);
        $this->get('/ai/'.$uuid.'/study')->assertNotFound();
    }
}
