<?php

namespace Modules\AppBuilder\Services;

use App\Models\User;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * The first small program is free only for someone a paying customer invited; a customer who came on their own pays.
 * An invite is worth money, so it is checked hard against inviting yourself. Every check must pass:
 * - the chat came from an invite link, and the account was opened after that link was opened (not before, not long after);
 * - the account came through Google and has no paid or free program yet;
 * - the inviter paid for a program here, so a made-up inviter account gives nothing;
 * - no shared address, network-plus-browser, browser mark or email (Gmail dots and +tags count as one) with the inviter;
 * - the browser, the address and the email never had a free build before, on any account;
 * - one inviter hands out only a few free builds a month.
 * The owner still approves the build, and sees who invited whom. Clearing cookies only drops the browser mark;
 * the address, network and email checks stay.
 */
class InvitedFreeBuild
{
    private const CHECKS = [
        'came_from_invite' => 'the chat did not come from someone else\'s invite link',
        'account_born_from_invite' => 'the account was not opened just after the invite link was opened',
        'google_account' => 'the account never signed in with Google',
        'first_program' => 'the account already has a paid or free program',
        'inviter_paid' => 'the inviter never paid for a program',
        'no_shared_address' => 'the inviter and the invitee share an internet address',
        'no_shared_network_and_browser' => 'the inviter and the invitee share a network and the same browser',
        'own_browser' => 'this browser was used by another account',
        'different_people_emails' => 'the inviter and the invitee have the same email',
        'never_free_before' => 'this browser, address or email already had a free build',
        'inviter_under_cap' => 'the inviter handed out too many free builds this month',
    ];

    /** The open price is the kind a free build covers: a small program, from an invite, not yet paid. */
    public function covers(BuilderProject $project): bool
    {
        return (bool) config('builder.free_build.enabled')
            && $project->status === BuilderProject::QUOTED
            && ! $project->free_fix
            && $project->referrer_user_id !== null
            && in_array($project->project_type, (array) config('builder.free_build.types'), true);
    }

    public function availableTo(BuilderProject $project, ?User $user, ?string $ip = null, ?string $userAgent = null): bool
    {
        return $user !== null && $this->covers($project) && $this->problems($project, $user, $ip, $userAgent) === [];
    }

    /** @return list<string> every check that failed, in plain words, for the owner and the log only */
    public function problems(BuilderProject $project, User $user, ?string $ip = null, ?string $userAgent = null): array
    {
        $who = new InviteParties($project, $user, $ip ?? (string) $project->ip, $userAgent ?? (string) $project->user_agent);

        return collect(self::CHECKS)
            ->reject(fn (string $problem, string $check) => $this->{$this->method($check)}($who))
            ->values()->all();
    }

    private function method(string $check): string
    {
        return 'check'.str_replace('_', '', ucwords($check, '_'));
    }

    private function checkCameFromInvite(InviteParties $who): bool
    {
        return $who->inviter !== null && $who->inviter->id !== $who->invitee->id;
    }

    private function checkAccountBornFromInvite(InviteParties $who): bool
    {
        $opened = $who->project->invited_at;
        $born = $who->invitee->created_at;

        return $opened !== null && $born !== null && $born->gte($opened)
            && $born->lte($opened->copy()->addDays((int) config('builder.free_build.account_days')));
    }

    private function checkGoogleAccount(InviteParties $who): bool
    {
        return $who->invitee->provider_id !== null;
    }

    private function checkFirstProgram(InviteParties $who): bool
    {
        return ! BuilderProject::where('user_id', $who->invitee->id)
            ->where(fn ($query) => $query->whereNotNull('paid_at')->orWhere('free_build', true))->exists();
    }

    private function checkInviterPaid(InviteParties $who): bool
    {
        return $who->inviter !== null && BuilderProject::where('user_id', $who->inviter->id)
            ->whereNotNull('paid_at')->where('free_build', false)->where('amount', '>', 0)->exists();
    }

    private function checkNoSharedAddress(InviteParties $who): bool
    {
        return $who->inviteeAddresses()->intersect($who->inviterAddresses())->isEmpty();
    }

    private function checkNoSharedNetworkAndBrowser(InviteParties $who): bool
    {
        $networks = $who->inviterAddresses()->map(fn (string $ip) => InviteParties::network($ip));

        return ! ($networks->contains(InviteParties::network($who->ip)) && $who->inviterBrowsers()->contains($who->userAgent));
    }

    private function checkOwnBrowser(InviteParties $who): bool
    {
        $device = $who->project->device_id;

        return $device !== null && ! BuilderProject::where('device_id', $device)->whereNotNull('user_id')->where('user_id', '!=', $who->invitee->id)->exists();
    }

    private function checkDifferentPeopleEmails(InviteParties $who): bool
    {
        return $who->inviter !== null && InviteParties::sameMailbox($who->inviter->email) !== InviteParties::sameMailbox($who->invitee->email);
    }

    private function checkNeverFreeBefore(InviteParties $who): bool
    {
        $free = BuilderProject::where('free_build', true)->with('user:id,email')->get(['id', 'ip', 'device_id', 'user_id']);
        $mailbox = InviteParties::sameMailbox($who->invitee->email);

        return $free->every(fn (BuilderProject $old) => ! $who->inviteeAddresses()->contains($old->ip)
            && ($old->device_id === null || $old->device_id !== $who->project->device_id)
            && ($old->user === null || InviteParties::sameMailbox($old->user->email) !== $mailbox));
    }

    private function checkInviterUnderCap(InviteParties $who): bool
    {
        return BuilderProject::where('referrer_user_id', $who->project->referrer_user_id)->where('free_build', true)
            ->where('created_at', '>=', now()->subDays(30))->count() < (int) config('builder.free_build.per_inviter_month');
    }
}
