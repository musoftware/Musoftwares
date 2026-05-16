<?php

namespace Modules\Core\Http\Middleware;

class ImpersonateMiddleware
{
    public function handle($request, \Closure $next)
    {
        if (session()->has('impersonate')) {
            auth()->loginUsingId(session('impersonate'));
        }
        return $next($request);
    }
}
