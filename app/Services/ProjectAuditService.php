<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectAuditService extends BaseService
{
    public function log(
        Project $project,
        string $action,
        array $changes = [],
        ?int $userId = null,
        ?string $ip = null,
    ): ProjectAuditLog {
        return ProjectAuditLog::create([
            'project_id' => $project->id,
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'changes' => $changes !== [] ? $changes : null,
            'ip' => $ip ?? request()?->ip(),
        ]);
    }

    public function logFromRequest(
        Project $project,
        string $action,
        array $changes = [],
        ?Request $request = null,
    ): ProjectAuditLog {
        $request ??= request();

        return $this->log(
            project: $project,
            action: $action,
            changes: $changes,
            userId: $request?->user()?->id,
            ip: $request?->ip(),
        );
    }
}
