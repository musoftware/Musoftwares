<?php

namespace Modules\AppBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/** Only the owner's worker, holding BUILDER_WORKER_TOKEN, may take jobs. No token configured means closed. */
class AuthenticateBuilderWorker
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('builder.worker_token');
        $given = (string) $request->bearerToken();

        if ($expected === '' || $given === '' || ! hash_equals($expected, $given)) {
            Log::warning('App builder: worker request refused', ['ip' => $request->ip(), 'path' => $request->path()]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        app(\Modules\AppBuilder\Services\WorkerRoles::class)->noteRequest($request);

        return $next($request);
    }
}
