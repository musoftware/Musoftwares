<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * A visitor who only plays with the chat (insults, sexual talk, joke requests) costs real model calls. The chat marks
 * such answers; after a few of them the visitor's chat closes and stays closed until the owner opens it again on the
 * admin page. A ban is a team message in the chat, so the owner sees it there. A signed-in visitor is banned by their
 * account; a guest by their address, and only for guests, so nobody who shares that address and has an account is hit.
 */
class ChatBans
{
    private const CLOSED = 'chat_closed';

    public function isBanned(?int $userId, ?string $ip): bool
    {
        return ($userId !== null || (string) $ip !== '') && $this->bans($userId, (string) $ip)->exists();
    }

    public function holds(BuilderProject $project): bool
    {
        return ! $this->isProtected($project) && $this->isBanned($project->user_id, $project->ip);
    }

    /** The chat marked one more answer as "not a serious message": the chat closes when that happened often enough. */
    public function countStrike(BuilderProject $project): bool
    {
        $strikes = $project->messages()->where('role', BuilderMessage::ASSISTANT)->where('payload->not_serious', true)->count();
        if ($this->isProtected($project) || $strikes < (int) config('builder.abuse.strikes')) {
            return false;
        }
        $this->ban($project);

        return true;
    }

    public function ban(BuilderProject $project): void
    {
        if ($this->isBanned($project->user_id, $project->ip)) {
            return;
        }

        $project->messages()->create([
            'role' => BuilderMessage::TEAM,
            'content' => __('builder.chat_closed', [], $project->customerLanguage()),
            'payload' => [self::CLOSED => true],
        ]);
        app(ProjectPayment::class)->tellOwner($project, 'Chat closed for a visitor who was not serious. Open it again from the admin page if this was wrong.', route('admin.builder.show', $project));
    }

    /** The owner opens the chat again for this visitor, in every chat of theirs. */
    public function lift(BuilderProject $project): int
    {
        return $this->bans($project->user_id, (string) $project->ip)->get()
            ->each(fn (BuilderMessage $ban) => $ban->update(['payload' => [self::CLOSED => false] + (array) $ban->payload]))
            ->count();
    }

    /** A paying customer or the team is never closed out by the chat's own judgement. */
    private function isProtected(BuilderProject $project): bool
    {
        return (float) $project->paid_total > 0 || $project->isImported() || (bool) $project->user?->isAdmin();
    }

    private function bans(?int $userId, string $ip): Builder
    {
        return BuilderMessage::query()->where('role', BuilderMessage::TEAM)->where('payload->'.self::CLOSED, true)
            ->whereHas('project', fn (Builder $project) => $userId !== null
                ? $project->where('user_id', $userId)
                : $project->whereNull('user_id')->where('ip', $ip));
    }
}
