<?php

namespace Modules\Core\Http\Middleware;

class AdminMiddleware
{
    public function handle($request, \Closure $next)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403);
        }
        return $next($request);
    }
}
