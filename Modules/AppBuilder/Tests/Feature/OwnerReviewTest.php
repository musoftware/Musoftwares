<?php

namespace Modules\AppBuilder\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;
use Modules\AppBuilder\Notifications\BuilderOwnerAlert;
use Modules\AppBuilder\Services\Chat\ChatModel;
use Modules\AppBuilder\Services\Chat\ChatTurn;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The owner stands between the customer and the engineer: every request reaches the owner's phone, opens on the
 * project's page with exactly what the engineer will read, and starts only after the owner approves it.
 */
class OwnerReviewTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'worker-secret-token';

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        // new work priced, as after the free first period; FreeChangesTest covers that period
        config(['builder.worker_token' => self::TOKEN, 'builder.owner_approval' => true, 'builder.free_changes_days' => 0]);
        Role::findOrCreate('admin');
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->owner->assignRole('admin');
    }

    private function worker(): array
    {
        return ['Authorization' => 'Bearer '.self::TOKEN, 'Accept' => 'application/json'];
    }

    private function deliveredProject(): BuilderProject
    {
        $project = BuilderProject::create([
            'ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'متجر', 'spec' => 'A shop.', 'project_type' => 'app_full',
            'status' => BuilderProject::READY, 'version' => 1, 'paid_total' => 150, 'built_at' => now(),
            'warranty_until' => now()->addDays((int) config('builder.warranty_days')),
        ]);
        $project->messages()->create(['role' => BuilderMessage::ASSISTANT, 'content' => 'تم التسليم.']);
        $this->withSession(['builder.started' => [$project->uuid]]);

        return $project;
    }

    /** @var list<ChatTurn> */
    public array $answers = [];

    private function customerAsks(BuilderProject $project, string $kind, string $text): BuilderRequest
    {
        $this->answers[] = ChatTurn::fromArray([
            'reply' => 'تمام',
            'needs_team' => false,
            'quote' => ['ready' => true, 'title' => $text, 'spec' => 'Note: '.$text, 'estimated_output_tokens' => 20000, 'kind' => $kind, 'complex' => false],
        ]);
        $this->app->instance(ChatModel::class, new class($this) implements ChatModel
        {
            public function __construct(private OwnerReviewTest $test) {}

            public function reply(string $system, array $turns): ChatTurn
            {
                return array_shift($this->test->answers);
            }
        });
        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => $text])->assertOk();

        return $project->requests()->latest('id')->firstOrFail();
    }

    public function test_a_free_fix_waits_for_the_owner_who_hears_on_the_phone(): void
    {
        $project = $this->deliveredProject();

        $item = $this->customerAsks($project, 'fix', 'زرار الاشتراك مش بيفتح');

        $this->assertSame(BuilderProject::READY, $project->refresh()->status);
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('requests.items.0.status', 'reviewing');
        Notification::assertSentTo($this->owner, BuilderOwnerAlert::class, fn (BuilderOwnerAlert $alert) => str_ends_with($alert->url, '#request-'.$item->id));
    }

    public function test_the_owner_corrects_the_brief_and_the_engineer_gets_the_corrected_words(): void
    {
        $project = $this->deliveredProject();
        $item = $this->customerAsks($project, 'fix', 'زرار الاشتراك مش بيفتح');

        $this->actingAs($this->owner)->post('/admin/builder/'.$project->uuid.'/requests/'.$item->id.'/approve', [
            'customer_words' => 'زرار الاشتراك مش بيفتح في صفحة البلس على أندرويد',
            'spec' => 'Fix the Plus subscribe button on Android.',
        ])->assertRedirect();

        $this->assertSame(BuilderProject::QUEUED, $project->refresh()->status);
        $spec = $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->json('job.spec');
        $this->assertStringContainsString('في صفحة البلس على أندرويد', $spec);
        $this->assertStringContainsString('Fix the Plus subscribe button on Android.', $spec);
    }

    public function test_a_paid_request_still_waits_and_the_owner_may_change_a_price_before_payment(): void
    {
        $project = $this->deliveredProject();
        $item = $this->customerAsks($project, 'build', 'قسم للبلس في الداشبورد');

        $this->actingAs($this->owner)->post('/admin/builder/'.$project->uuid.'/requests/'.$item->id.'/save', ['amount' => 30])->assertRedirect();
        $this->assertSame('30.00', $item->refresh()->amount);
        $this->assertSame('20.0000', $item->budget_usd);

        app(\Modules\AppBuilder\Services\ProjectRequests::class)->markPaid($project, [$item->id], 'test');
        $this->assertSame(BuilderProject::READY, $project->refresh()->status);

        $this->actingAs($this->owner)->post('/admin/builder/'.$project->uuid.'/requests/'.$item->id.'/approve', ['amount' => 1])->assertRedirect();
        // paid: the price stays what the customer paid
        $this->assertSame('30.00', $item->refresh()->amount);
        $this->assertSame(BuilderProject::QUEUED, $project->refresh()->status);
    }

    public function test_a_turned_down_request_leaves_the_list_and_the_customer_reads_why(): void
    {
        $project = $this->deliveredProject();
        $item = $this->customerAsks($project, 'build', 'عايز لعبة كاملة جوه التطبيق');

        $this->actingAs($this->owner)->post('/admin/builder/'.$project->uuid.'/requests/'.$item->id.'/reject', ['note' => 'دي محتاجة مشروع لوحدها، نتكلم فيها.'])->assertRedirect();

        $this->assertSoftDeleted($item);
        $this->assertSame('دي محتاجة مشروع لوحدها، نتكلم فيها.', $project->messages()->reorder('id', 'desc')->value('content'));
    }

    public function test_only_an_admin_reviews_and_the_test_push_reaches_every_admin(): void
    {
        $project = $this->deliveredProject();
        $item = $this->customerAsks($project, 'fix', 'زرار');
        $customer = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($customer)->post('/admin/builder/'.$project->uuid.'/requests/'.$item->id.'/approve')->assertForbidden();

        $this->actingAs($this->owner)->post('/admin/builder/push-test')->assertRedirect();
        Notification::assertSentTo($this->owner, BuilderOwnerAlert::class, fn (BuilderOwnerAlert $alert) => str_contains($alert->body, 'Test notification'));
    }

    public function test_the_owner_also_gets_a_telegram_message_with_a_button_to_the_request(): void
    {
        config(['services.site_telegram.bot_token' => 'test-token']);
        // the owner linked Telegram from the settings page
        $this->owner->forceFill(['telegram_chat_id' => '12345'])->save();
        \Illuminate\Support\Facades\Http::fake(['api.telegram.org/*' => \Illuminate\Support\Facades\Http::response(['ok' => true])]);
        $project = $this->deliveredProject();

        $item = $this->customerAsks($project, 'build', 'قسم للبلس في الداشبورد');

        \Illuminate\Support\Facades\Http::assertSent(fn ($request) => str_contains($request->url(), '/bottest-token/sendMessage')
            && $request['chat_id'] === '12345'
            && str_contains($request['text'], 'قسم للبلس في الداشبورد')
            && str_ends_with($request['reply_markup']['inline_keyboard'][0][0]['url'], '#request-'.$item->id));
    }

    public function test_the_chat_knows_a_paid_request_is_paid_and_only_the_owner_sees_the_review_link(): void
    {
        $project = $this->deliveredProject();
        $item = $this->customerAsks($project, 'build', 'قسم للبلس في الداشبورد');
        app(\Modules\AppBuilder\Services\ProjectRequests::class)->markPaid($project, [$item->id], 'admin-test');

        // a bare "ready" made the chat tell the customer the request was not paid
        $this->assertStringContainsString('PAID; the engineer is reviewing it', app(\Modules\AppBuilder\Services\ProjectRequests::class)->forPrompt($project->refresh()));

        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('requests.items.0.review_url', null);
        $this->actingAs($this->owner)->getJson('/ai/'.$project->uuid.'/state')
            ->assertJsonPath('requests.items.0.review_url', route('admin.builder.show', $project).'#request-'.$item->id);
    }

    public function test_a_paid_new_project_waits_for_the_owner_who_approves_the_brief(): void
    {
        $project = BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'منيو', 'spec' => 'A menu page.', 'amount' => 40, 'status' => BuilderProject::QUOTED, 'quote_version' => 1]);

        $this->actingAs($this->owner)->post('/admin/builder/'.$project->uuid.'/mark-paid', ['version' => 1]);
        auth()->logout();

        $this->assertTrue($project->refresh()->waitsForOwner());
        Notification::assertSentTo($this->owner, BuilderOwnerAlert::class, fn (BuilderOwnerAlert $alert) => str_ends_with($alert->url, '#approve'));
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertNoContent();

        $this->actingAs($this->owner)->post('/admin/builder/'.$project->uuid.'/approve', ['spec' => 'A menu page with prices in EGP.'])->assertRedirect();
        auth()->logout();

        $this->assertSame('A menu page with prices in EGP.', $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->json('job.spec'));
    }
}
