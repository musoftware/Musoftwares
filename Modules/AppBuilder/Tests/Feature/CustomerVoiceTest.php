<?php

namespace Modules\AppBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\Chat\ChatModel;
use Modules\AppBuilder\Services\Chat\ChatTurn;
use Modules\AppBuilder\Services\Chat\ChatUnavailable;
use Modules\AppBuilder\Services\JobQueue;
use Tests\TestCase;

/** The engineer's delivery note reaches the customer retold in plain words; the owner keeps the engineer's own note. */
class CustomerVoiceTest extends TestCase
{
    use \Modules\AppBuilder\Tests\SignsInWithGoogle, RefreshDatabase;

    private const ENGINEER_NOTE = 'Test script now runs in 1m40s; 538/539 backend tests pass; flutter analyze clean; run php artisan migrate.';

    /** @var list<string> what the contact was asked to retell */
    public array $asked = [];

    public ?ChatTurn $answer = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInWithGoogle();
        Storage::fake('local');
        config(['builder.retell_delivery' => true]);
        $this->app->instance(ChatModel::class, new class($this) implements ChatModel
        {
            public function __construct(private CustomerVoiceTest $test) {}

            public function reply(string $system, array $turns): ChatTurn
            {
                $this->test->asked[] = $turns[0]['content'];

                return $this->test->answer ?? throw new ChatUnavailable('down');
            }
        });
    }

    private function deliver(): BuilderMessage
    {
        $project = BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'ZainArena', 'status' => BuilderProject::BUILDING, 'version' => 1, 'imported_at' => now()]);
        app(JobQueue::class)->complete($project, null, self::ENGINEER_NOTE, 1.5);

        return $project->messages()->reorder('id', 'desc')->firstOrFail();
    }

    public function test_the_customer_reads_the_plain_retelling_and_the_engineers_note_is_kept(): void
    {
        $this->answer = ChatTurn::fromArray(['reply' => 'خلّصنا: الخبر الحصري بيبان بقفل، ولما حد يدوس عليه بتفتحله صفحة الاشتراك.', 'quote' => []]);

        $message = $this->deliver();

        $this->assertSame([self::ENGINEER_NOTE], $this->asked);
        $this->assertStringContainsString('صفحة الاشتراك', $message->content);
        $this->assertStringNotContainsString('538', $message->content);
        $this->assertSame(self::ENGINEER_NOTE, $message->payload['engineer_note']);
    }

    public function test_a_delivery_never_waits_for_the_retelling(): void
    {
        $message = $this->deliver();

        $this->assertSame(self::ENGINEER_NOTE, $message->content);
    }
}
