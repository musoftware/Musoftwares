<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ModeratorMiddleware
 *
 * Allows both admin and moderator roles to pass through.
 * Use this on routes that moderators are permitted to access.
 * For routes that are admin-only, continue using AdminMiddleware.
 */
class ModeratorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->hasAnyRole(['admin', 'Admin', 'super_admin', 'superadmin', 'moderator'])) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
