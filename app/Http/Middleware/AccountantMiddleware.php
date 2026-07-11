<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AccountantMiddleware
 *
 * Allows admin, super_admin, and accountant roles to pass through.
 */
class AccountantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->hasAnyRole(['admin', 'Admin', 'super_admin', 'superadmin', 'accountant'])) {
            abort(403, __('general.unauthorized_access'));
        }

        return $next($request);
    }
}
