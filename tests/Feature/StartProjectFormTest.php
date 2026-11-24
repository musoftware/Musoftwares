<?php

namespace Tests\Feature;

use App\Models\GuestTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The start-project form now asks plain questions first (what you need, your business, the problem)
 * and keeps technical details optional. It still posts the same keys to /tools/lead-capture.
 */
class StartProjectFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_form_with_its_text_in_both_languages(): void
    {
        foreach (['ar' => 'إيه اللي محتاجه؟', 'en' => 'What do you need?'] as $lang => $question) {
            $this->get('/start-project?lang='.$lang)->assertOk()->assertInertia(fn (Assert $page) => $page->component('Public/Wizard/SystemBuilder'));
            $this->assertSame($question, __('start_project.solution_label', [], $lang));
        }
    }

    public function test_every_start_project_key_exists_in_both_languages(): void
    {
        $ar = require lang_path('ar/start_project.php');
        $en = require lang_path('en/start_project.php');
        $this->assertSame(array_keys($ar), array_keys($en));

        foreach (['ar', 'en'] as $lang) {
            $json = json_decode((string) file_get_contents(resource_path("js/lang/{$lang}.json")), true);
            $this->assertSame(require lang_path("{$lang}/start_project.php"), $json['start_project']);
        }
    }

    public function test_non_technical_request_with_only_name_and_phone_is_saved(): void
    {
        $this->postJson('/tools/lead-capture', [
            'name' => 'Mona',
            'mobile' => '+20 100 000 0000',
            'email' => '',
            'business' => '',
            'tool_name' => 'Start project',
            'scope' => [
                'brief_code' => 'MS-SYS-1234',
                'solution' => 'not_sure',
                'industry' => '',
                'goal' => 'نسجل المبيعات في كشكول',
                'archetype' => '',
                'platforms' => [],
                'modules' => [],
                'notes' => 'نسجل المبيعات في كشكول',
            ],
        ])->assertOk()->assertJson(['success' => true]);

        $ticket = GuestTicket::sole();
        $this->assertStringContainsString('Start project', $ticket->subject);
        $this->assertStringContainsString('solution: not_sure', $ticket->body);
        $this->assertStringContainsString('نسجل المبيعات في كشكول', $ticket->body);
        $this->assertStringContainsString('MS-SYS-1234', $ticket->body);
    }

    public function test_missing_name_or_phone_is_rejected_per_field(): void
    {
        $this->postJson('/tools/lead-capture', ['scope' => ['solution' => 'website']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'mobile']);

        $this->assertDatabaseCount('guest_tickets', 0);
    }
}
