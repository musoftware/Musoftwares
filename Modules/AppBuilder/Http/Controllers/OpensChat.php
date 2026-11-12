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
        $project->update(['country' => Dialect::countryOf($request)]);
        // this browser started the chat, so it keeps it even after the chat joins an account at payment
        OnlyProjectOwner::rememberStarter($request, $project);

        return $project;
    }
}
