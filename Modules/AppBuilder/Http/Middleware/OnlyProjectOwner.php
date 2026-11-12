<?php

namespace Modules\AppBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\AppBuilder\Models\BuilderProject;
use Symfony\Component\HttpFoundation\Response;

/**
 * A chat that belongs to an account opens only for that account and for admins. A visitor who is not signed in is
 * sent to sign in first; another account gets "not found", so a shared or leaked link shows nothing. The browser
 * that started the chat keeps it for its session (a guest who pays gets an account at that moment and must not be
 * locked out on the way back from the payment page). A chat with no account yet stays open to whoever has its link.
 */
class OnlyProjectOwner
{
    private const SESSION_KEY = 'builder.started';

    /** At most this many chats are remembered per browser session. */
    private const REMEMBERED = 50;

    public static function rememberStarter(Request $request, BuilderProject $project): void
    {
        $started = array_slice([...(array) $request->session()->get(self::SESSION_KEY, []), $project->uuid], -self::REMEMBERED);
        $request->session()->put(self::SESSION_KEY, $started);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');
        if (! $project instanceof BuilderProject || $project->user_id === null || $this->startedHere($request, $project)) {
            return $next($request);
        }

        $user = $request->user();
        if ($user !== null && ($user->id === $project->user_id || $user->isAdmin())) {
            return $next($request);
        }
        abort_if($user !== null, 404);

        return $request->expectsJson()
            ? response()->json(['message' => __('builder.errors.sign_in_to_open')], 401)
            : redirect()->route('login', ['redirect' => $request->getRequestUri()]);
    }

    private function startedHere(Request $request, BuilderProject $project): bool
    {
        return $request->hasSession() && in_array($project->uuid, (array) $request->session()->get(self::SESSION_KEY, []), true);
    }
}
