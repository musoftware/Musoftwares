<?php

namespace App\Http\Middleware;

use Closure;

class ForceJsonRequest
{
    public function handle($request, Closure $next)
    {
        // Allow only application/json
        if (!$request->isJson()) {
            return response()->json([
                'error' => 'Only JSON requests are allowed.',
            ], 415); // Unsupported Media Type
        }

        return $next($request);
    }
}
