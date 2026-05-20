<?php

namespace Modules\ERP\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnforceTeamMemberPermissions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only run if authenticated as an erp_team member
        if (Auth::guard('erp_team')->check()) {
            $member = Auth::guard('erp_team')->user();

            if ($member->isMember()) {
                // If it's a mutating request (POST, PUT, PATCH, DELETE)
                if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                    
                    // Allow task management (tasks, items)
                    $route = $request->route();
                    $routeName = $route ? $route->getName() : '';
                    $uri = $request->getRequestUri();

                    $isTaskRequest = str_contains($routeName, 'tasks') || 
                                    str_contains($uri, '/tasks') || 
                                    str_contains($uri, '/notes') ||
                                    str_contains($routeName, 'notes');

                    if (!$isTaskRequest) {
                        if ($request->expectsJson()) {
                            return response()->json(['message' => 'Unauthorized. Team members have read-only access to this section.'], 403);
                        }
                        return back()->withErrors(['error' => 'Unauthorized. Team members have read-only access to this section.']);
                    }
                }
            }
        }

        return $next($request);
    }
}
