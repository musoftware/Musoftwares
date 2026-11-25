<?php

namespace Modules\AppBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppBuilder\Models\BuilderProject;
use Tests\TestCase;

/**
 * After the start-project form, the visitor can continue in the /ai chat. The link carries their answers
 * in ?brief=, which only fills the message box on the start page. Nothing is sent until they press Send.
 */
class ChatBriefFromStartProjectTest extends TestCase
{
    use \Modules\AppBuilder\Tests\SignsInWithGoogle, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInWithGoogle();
    }

    public function test_brief_fills_the_message_box_escaped_and_nothing_is_sent(): void
    {
        $brief = "أهلًا، عايز أعمل مشروع.\nالمطلوب: متجر إلكتروني <script>alert(1)</script>";

        $this->get('/ai?brief='.rawurlencode($brief))
            ->assertOk()
            ->assertSee('>'.e($brief).'</textarea>', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->assertDatabaseCount('builder_projects', 0);
    }

    public function test_brief_is_cut_to_the_message_limit(): void
    {
        config(['builder.limits.message_chars' => 20]);

        $this->get('/ai?brief='.str_repeat('a', 50))->assertOk()->assertSee('>'.str_repeat('a', 20).'</textarea>', false);
    }

    public function test_without_brief_the_box_stays_empty(): void
    {
        $this->get('/ai')->assertOk()->assertSee('autofocus></textarea>', false);
    }

    public function test_an_existing_chat_ignores_brief(): void
    {
        $project = BuilderProject::create(['user_id' => $this->customer->id]);

        $this->get("/ai/{$project->uuid}?brief=hello")->assertOk()->assertSee('autofocus></textarea>', false);
    }
}
