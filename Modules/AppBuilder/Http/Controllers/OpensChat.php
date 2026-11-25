<?php

namespace Modules\AppBuilder\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AppBuilder\Http\Middleware\OnlyProjectOwner;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\BuilderReferral;
use Modules\AppBuilder\Services\Chat\ChatLimitReached;
use Modules\AppBuilder\Services\Chat\Dialect;
use Modules\AppBuilder\Services\Chat\ProjectChat;

/** Opens a new chat for this visitor: the daily limit, whose link they came from, their country, and this browser keeps it. */
trait OpensChat
{
    /** @throws ChatLimitReached */
    private function openChat(Request $request): BuilderProject
    {
        // the owner and the team test as often as they need
        $unlimited = (bool) $request->user()?->isAdmin();
        $project = app(ProjectChat::class)->start((string) $request->ip(), app()->getLocale(), app(BuilderReferral::class)->referrerFor($request), $request->user()?->id, $unlimited, $request->userAgent());
        $project->update([
            'country' => Dialect::countryOf($request),
            // who is behind the chat, for the checks on a free first build (InvitedFreeBuild)
            'device_id' => app(\Modules\AppBuilder\Services\DeviceMark::class)->of($request),
            'invited_at' => $project->referrer_user_id ? self::inviteOpenedAt($request) : null,
        ]);
        // this browser started the chat, so it keeps it even after the chat joins an account at payment
        OnlyProjectOwner::rememberStarter($request, $project);

        return $project;
    }

    /** When this browser opened the invite link (/r/{code}); null for an old session that never kept it. */
    private static function inviteOpenedAt(Request $request): ?\Illuminate\Support\Carbon
    {
        $at = (int) $request->session()->get('referral_opened_at', 0);

        return $at > 0 ? \Illuminate\Support\Carbon::createFromTimestamp($at) : null;
    }
}
