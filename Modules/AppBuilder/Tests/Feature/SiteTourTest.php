<?php

namespace Modules\AppBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderSiteTour;
use Modules\AppBuilder\Services\Chat\ChatModel;
use Modules\AppBuilder\Services\Chat\ChatTurn;
use Modules\AppBuilder\Services\QuotePricer;
use Tests\TestCase;

/** A customer who wants a copy of a site: the worker walks through it (with a test login) before the price. */
class SiteTourTest extends TestCase
{
    use \Modules\AppBuilder\Tests\SignsInWithGoogle, RefreshDatabase;

    private const TOKEN = 'worker-secret-token';

    private const PASSWORD = 'Test-Account-Pass-77';

    /** @var list<ChatTurn> */
    private array $script = [];

    /** @var list<array> what the chat model was given on each call */
    private array $seen = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInWithGoogle();
        Storage::fake('local');
        config(['mail.default' => 'array', 'builder.worker_token' => self::TOKEN]);

        // Claude is the only part replaced: a scripted model that answers in order and keeps what it was shown.
        $this->app->instance(ChatModel::class, new class($this->script, $this->seen) implements ChatModel
        {
            public function __construct(private array &$script, private array &$seen) {}

            public function reply(string $system, array $turns): ChatTurn
            {
                $this->seen[] = $turns;

                return array_shift($this->script);
            }
        });
    }

    private function answer(string $reply, string $tourSite = '', array $secrets = []): void
    {
        $this->script[] = ChatTurn::fromArray([
            'reply' => $reply,
            'needs_team' => false,
            'quote' => ['ready' => false, 'title' => '', 'spec' => '', 'estimated_output_tokens' => 0, 'kind' => 'build', 'complex' => false, 'project_type' => 'clone'],
            'tour_site' => $tourSite,
            'secrets' => $secrets,
        ]);
    }

    private function worker(): array
    {
        return ['Authorization' => 'Bearer '.self::TOKEN, 'Accept' => 'application/json'];
    }

    private function picture(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
    }

    private function cloneChat(): BuilderProject
    {
        $this->answer('هلف في الموقع وأرجعلك', 'https://shop.example.com');
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز نسخة من موقع https://shop.example.com'])->assertOk()->json('uuid');

        return BuilderProject::where('uuid', $uuid)->firstOrFail();
    }

    public function test_the_worker_walks_the_site_and_the_chat_answers_from_its_pictures(): void
    {
        $project = $this->cloneChat();
        $this->getJson("/ai/{$project->uuid}/state")->assertOk()->assertJsonPath('touring', true);

        $tours = $this->getJson('/api/builder/worker/tours', $this->worker())->assertOk()->json('tours');
        $this->assertCount(1, $tours);
        $this->assertSame('https://shop.example.com', $tours[0]['url']);
        $this->assertNull($tours[0]['login']);
        $this->assertSame([], $this->getJson('/api/builder/worker/tours', $this->worker())->json('tours'));

        $this->answer('شفت 2 صفحات: الرئيسية وصفحة المنتج. السعر من 200 دولار.');
        $this->post("/api/builder/worker/tours/{$tours[0]['id']}", [
            'ok' => '1',
            'logged_in' => '0',
            'report' => "## Page 1: Home\nMenu: Shop, Cart\n\n## Page 2: Product\nForm: quantity, Add to cart",
            'pictures' => [$this->picture('01-home.png'), $this->picture('02-product.png')],
        ], $this->worker())->assertOk();

        $tour = BuilderSiteTour::firstOrFail();
        $this->assertSame(BuilderSiteTour::DONE, $tour->status);
        $this->assertSame(2, $tour->pages_seen);
        $message = $project->messages()->where('role', BuilderMessage::TOUR)->firstOrFail();
        $this->assertCount(2, $message->attachments);

        // the chat model got the pictures and the notes, and its answer is on the page
        $last = end($this->seen[1]);
        $texts = collect($last['content'])->where('type', 'text')->pluck('text')->implode("\n");
        $this->assertStringContainsString('Page 2: Product', $texts);
        $this->assertSame(2, collect($last['content'])->where('type', 'image')->count());
        $state = $this->getJson("/ai/{$project->uuid}/state")->assertOk()->assertJsonPath('touring', false);
        $tourLine = collect($state->json('messages'))->firstWhere('role', 'tour');
        $this->assertCount(2, $tourLine['images']);
        $this->assertStringContainsString('السعر من 200', collect($state->json('messages'))->last()['text']);

        // a second report for the same tour changes nothing
        $this->post("/api/builder/worker/tours/{$tours[0]['id']}", ['ok' => '1', 'pictures' => [$this->picture('x.png')]], $this->worker())->assertOk();
        $this->assertSame(1, $project->messages()->where('role', BuilderMessage::TOUR)->count());
    }

    public function test_a_test_login_typed_in_the_chat_goes_to_the_worker_and_is_masked(): void
    {
        $project = $this->cloneChat();
        BuilderSiteTour::query()->update(['status' => BuilderSiteTour::FAILED]);

        $this->answer('تمام، هدخل بالحساب ده', 'https://shop.example.com', [
            ['name' => 'clone_login_url', 'value' => 'https://shop.example.com/login'],
            ['name' => 'clone_username', 'value' => 'tester@example.com'],
            ['name' => 'clone_password', 'value' => self::PASSWORD],
        ]);
        $this->postJson("/ai/{$project->uuid}/messages", ['text' => 'الحساب tester@example.com والباسورد '.self::PASSWORD])->assertOk();

        $this->assertStringNotContainsString(self::PASSWORD, (string) $project->lastCustomerMessage()->content);
        $tours = $this->getJson('/api/builder/worker/tours', $this->worker())->json('tours');
        $this->assertSame(['url' => 'https://shop.example.com/login', 'username' => 'tester@example.com', 'password' => self::PASSWORD], $tours[0]['login']);
    }

    public function test_a_tour_nobody_takes_ends_and_the_chat_goes_on(): void
    {
        $project = $this->cloneChat();
        $this->travel(4)->minutes();

        $this->answer('معلش مقدرتش ألف في الموقع دلوقتي، ابعتلي صور للشاشات الأساسية.');
        $this->getJson("/ai/{$project->uuid}/state")->assertOk()->assertJsonPath('touring', false);

        $this->assertSame(BuilderSiteTour::FAILED, BuilderSiteTour::firstOrFail()->status);
        $this->assertStringContainsString('ابعتلي صور', $project->messages()->reorder('id', 'desc')->first()->content);
    }

    public function test_tours_stop_after_the_daily_limit(): void
    {
        config(['builder.tours.per_day' => 1]);
        $project = $this->cloneChat();
        BuilderSiteTour::query()->update(['status' => BuilderSiteTour::FAILED]);

        $this->answer('هلف تاني', 'https://shop.example.com');
        $this->postJson("/ai/{$project->uuid}/messages", ['text' => 'لف تاني'])->assertOk();

        $this->assertSame(1, BuilderSiteTour::count());
    }

    public function test_a_copy_costs_at_least_200_and_more_when_the_work_is_bigger(): void
    {
        $pricer = app(QuotePricer::class);

        $this->assertSame(200.0, (float) $pricer->price(10_000, false, 0, 'clone')['amount']);
        // when the real work costs more than the floor, the price follows the work
        config(['builder.pricing.markup' => 60]);
        $this->assertGreaterThan(200.0, (float) $pricer->price(200_000, true, 0, 'clone')['amount']);
    }
}
