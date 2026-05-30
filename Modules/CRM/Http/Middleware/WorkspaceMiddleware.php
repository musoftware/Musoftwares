<?php

namespace Modules\CRM\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\CRM\Models\Workspace;
use Modules\CRM\Infrastructure\Context\TenantContext;

class WorkspaceMiddleware
{
    /**
     * Handle an incoming request.
     * Ensure the user has an active CRM workspace.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if there is an active workspace in session
        $workspaceId = session('crm_workspace_id');

        if (!$workspaceId) {
            // Find a workspace the user owns or belongs to
            $workspace = Workspace::where('user_id', $user->id)
                ->orWhereHas('users', fn($q) => $q->where('users.id', $user->id))
                ->first();

            if ($workspace) {
                session(['crm_workspace_id' => $workspace->id]);
                $workspaceId = $workspace->id;
            } else {
                // Since the user already passed the 'subscription:crm' middleware, 
                // they are authorized. Auto-create their default workspace.
                $workspace = Workspace::create([
                    'user_id' => $user->id,
                    'name' => $user->name . "'s Workspace",
                ]);
                
                session(['crm_workspace_id' => $workspace->id]);
                $workspaceId = $workspace->id;
            }
        }

        // Add the workspace ID to the TenantContext singleton
        $tenantContext = app(TenantContext::class);
        $tenantContext->setWorkspaceId($workspaceId);

        // Check and set branch_id if applicable
        $branchId = session('crm_branch_id');
        if ($branchId) {
            $tenantContext->setBranchId($branchId);
        }

        return $next($request);
    }
}
