<?php

namespace Modules\AppBuilder\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Notifications\BuilderOwnerAlert;
use Throwable;

/** Pushes to every admin's devices; a failed push is logged and never stops the work that caused it. */
class OwnerAlerts
{
    /** @return int how many admins it was sent to */
    public function send(BuilderProject $project, string $line, ?string $url = null): int
    {
        // whereHas, not User::role(), because role() throws when the "admin" role does not exist yet
        $admins = User::whereHas('roles', fn ($roles) => $roles->where('name', 'admin'))->get();
        if ($admins->isEmpty()) {
            return 0;
        }

        try {
            Notification::send($admins, new BuilderOwnerAlert(
                mb_substr((string) ($project->title ?: 'AI builder'), 0, 120),
                mb_substr($line, 0, 400),
                $url ?? route('admin.builder.show', $project),
            ));
        } catch (Throwable $error) {
            Log::warning('App builder: owner push failed', ['project_id' => $project->id, 'error' => $error->getMessage()]);

            return 0;
        }

        return $admins->count();
    }
}
