<?php

namespace Modules\AppBuilder\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\InvitedFreeBuild;
use Modules\AppBuilder\Services\InviteParties;
use Tests\TestCase;

/** The first small program is free only for someone a paying customer invited, never for inviting yourself. */
class InvitedFreeBuildTest extends TestCase
{
    use RefreshDatabase;

    private const INVITEE_IP = '41.10.20.30';

    private const INVITEE_BROWSER = 'Mozilla/5.0 (Invitee phone)';

    private User $inviter;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array']);
        $this->inviter = User::factory()->google()->create(['email' => 'mahmoud.ali@gmail.com', 'onboarding_completed' => true]);
        // the inviter is a real customer: a paid program, from their own place and browser
        BuilderProject::create(['ip' => '156.200.1.5', 'user_agent' => 'Mozilla/5.0 (Inviter laptop)', 'device_id' => str_repeat('A', 32), 'locale' => 'ar', 'currency' => 'USD',
            'user_id' => $this->inviter->id, 'amount' => 60, 'paid_total' => 60, 'paid_at' => now()->subWeek(), 'status' => BuilderProject::READY, 'quote_version' => 1]);
    }

    /** An invite opened an hour ago, a Google account opened after it, a page priced and waiting. */
    private function invitedChat(array $invitee = [], array $project = []): BuilderProject
    {
        $user = User::factory()->google()->create($invitee + ['email' => 'sara@gmail.com', 'onboarding_completed' => true, 'created_at' => now()->subMinutes(50)]);

        return BuilderProject::create($project + [
            'ip' => self::INVITEE_IP, 'user_agent' => self::INVITEE_BROWSER, 'device_id' => str_repeat('B', 32), 'locale' => 'ar', 'currency' => 'USD',
            'user_id' => $user->id, 'referrer_user_id' => $this->inviter->id, 'invited_at' => now()->subHour(),
            'title' => 'Menu', 'spec' => 'A menu page.', 'amount' => 3, 'budget_usd' => 2, 'project_type' => 'page', 'status' => BuilderProject::QUOTED, 'quote_version' => 1,
        ]);
    }

    private function problems(BuilderProject $project): array
    {
        return app(InvitedFreeBuild::class)->problems($project, $project->user()->first(), self::INVITEE_IP, self::INVITEE_BROWSER);
    }

    private function asInvitee(BuilderProject $project): self
    {
        return $this->actingAs($project->user()->first())->withServerVariables(['REMOTE_ADDR' => self::INVITEE_IP, 'HTTP_USER_AGENT' => self::INVITEE_BROWSER]);
    }

    public function test_a_real_invitee_starts_the_first_page_free_and_the_owner_still_approves_it(): void
    {
        config(['builder.owner_approval' => true]);
        $project = $this->invitedChat();
        $this->assertSame([], $this->problems($project));

        $state = $this->asInvitee($project)->getJson("/ai/{$project->uuid}/state")->assertOk();
        $this->assertNotNull($state->json('quote.free_build.url'));

        $this->asInvitee($project)->postJson("/ai/{$project->uuid}/free")->assertOk();
        $project->refresh();
        $this->assertTrue($project->free_build);
        $this->assertSame(BuilderProject::QUEUED, $project->status);
        $this->assertLessThanOrEqual(1.0, (float) $project->budget_usd);
        $this->assertTrue($project->waitsForOwner());

        // the same account gets one free build only
        $second = $this->invitedChat(['email' => 'other@example.com'], ['user_id' => $project->user_id, 'device_id' => str_repeat('C', 32)]);
        $this->assertContains('the account already has a paid or free program', $this->problems($second));
    }

    public function test_someone_who_came_on_their_own_pays(): void
    {
        $project = $this->invitedChat(project: ['referrer_user_id' => null]);

        $this->asInvitee($project)->getJson("/ai/{$project->uuid}/state")->assertOk()->assertJsonPath('quote.free_build', null);
        $this->asInvitee($project)->postJson("/ai/{$project->uuid}/free")->assertStatus(409);
        $this->assertFalse($project->refresh()->free_build);
    }

    public function test_an_account_opened_before_the_invite_is_not_an_invitee(): void
    {
        $project = $this->invitedChat(['created_at' => now()->subDays(3)]);

        $this->assertSame(['the account was not opened just after the invite link was opened'], $this->problems($project));
    }

    public function test_an_inviter_who_never_paid_gives_nothing(): void
    {
        BuilderProject::where('user_id', $this->inviter->id)->update(['paid_at' => null, 'paid_total' => 0]);

        $this->assertContains('the inviter never paid for a program', $this->problems($this->invitedChat()));
    }

    public function test_inviting_yourself_from_the_same_place_or_browser_is_caught(): void
    {
        $sameAddress = $this->invitedChat(project: ['ip' => '156.200.1.5']);
        $this->assertContains('the inviter and the invitee share an internet address', app(InvitedFreeBuild::class)->problems($sameAddress, $sameAddress->user()->first(), '156.200.1.5', self::INVITEE_BROWSER));

        // the inviter's sign-in session counts too, and the same browser on the same home network
        DB::table('sessions')->insert(['id' => 'inviter-session', 'user_id' => $this->inviter->id, 'ip_address' => '41.10.20.99', 'user_agent' => self::INVITEE_BROWSER, 'payload' => '', 'last_activity' => time()]);
        $this->assertContains('the inviter and the invitee share a network and the same browser', $this->problems($this->invitedChat(['email' => 'n@example.com'])));

        $sameBrowser = $this->invitedChat(['email' => 'b@example.com'], ['device_id' => str_repeat('A', 32)]);
        $this->assertContains('this browser was used by another account', $this->problems($sameBrowser));
    }

    public function test_the_same_gmail_written_differently_is_the_same_person(): void
    {
        $this->assertSame(InviteParties::sameMailbox('mahmoud.ali@gmail.com'), InviteParties::sameMailbox('MahmoudAli+free@googlemail.com'));

        $project = $this->invitedChat(['email' => 'MahmoudAli+free@googlemail.com']);
        $this->assertContains('the inviter and the invitee have the same email', $this->problems($project));
    }

    public function test_a_browser_or_address_that_had_a_free_build_never_gets_another(): void
    {
        $first = $this->invitedChat();
        $first->update(['free_build' => true, 'status' => BuilderProject::QUEUED]);

        $again = $this->invitedChat(['email' => 'new.person@example.com'], ['device_id' => str_repeat('D', 32)]);
        $this->assertContains('this browser, address or email already had a free build', $this->problems($again));
    }

    public function test_one_inviter_hands_out_only_a_few_a_month(): void
    {
        config(['builder.free_build.per_inviter_month' => 1]);
        BuilderProject::create(['ip' => '8.8.4.4', 'locale' => 'ar', 'currency' => 'USD', 'referrer_user_id' => $this->inviter->id, 'free_build' => true, 'status' => BuilderProject::READY, 'quote_version' => 1]);

        $this->assertContains('the inviter handed out too many free builds this month', $this->problems($this->invitedChat()));
    }

    public function test_a_bigger_program_is_never_free(): void
    {
        $project = $this->invitedChat(project: ['project_type' => 'system']);

        $this->assertFalse(app(InvitedFreeBuild::class)->covers($project));
    }

    public function test_the_invite_link_starts_the_clock_and_a_new_chat_keeps_it(): void
    {
        $code = \App\Models\UserReferral::create(['user_id' => $this->inviter->id, 'title' => 'ai', 'key' => 'inv123', 'slug' => 'inv123'])->key;
        $this->get("/r/{$code}/ai");
        $opened = session('referral_opened_at');
        $this->assertNotNull($opened);

        $this->travel(5)->seconds();
        $this->get("/r/{$code}/ai");
        $this->assertSame($opened, session('referral_opened_at'));
    }
}
