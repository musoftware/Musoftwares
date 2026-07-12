<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Project;

class VerifyBoardAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Resolve project
        $project = $request->route('project');
        if (!$project instanceof Project) {
            $projectId = $request->route('project');
            $project = Project::find($projectId);
        }

        if (!$project) {
            abort(404, 'Project not found.');
        }

        // 2. If user is authenticated, check if they are admin or owner of the project,
        // or have shared access in session.
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->isAdmin() || $project->user_id === $user->id || session()->get("shared_project_write_access.{$project->id}")) {
                return $next($request);
            }
        }

        // 3. Otherwise, check if guest has the write access session flag
        if (session()->get("shared_project_write_access.{$project->id}")) {
            return $next($request);
        }

        abort(403, 'You do not have access to this board.');
    }
}
