<?php

namespace Modules\CRM\app\Core;

use Modules\CRM\Models\Activity;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Log a CRM action automatically tying it to the active workspace.
     *
     * @param string $event The event name (e.g. 'lead.created')
     * @param Model $entity The related eloquent model (e.g. Lead, Task, Campaign)
     * @param array|null $oldValue
     * @param array|null $newValue
     * @param array|null $metadata
     * @return Activity|null
     */
    public function log(string $event, Model $entity, ?array $oldValue = null, ?array $newValue = null, ?array $metadata = null): ?Activity
    {
        $workspaceId = session('crm_workspace_id');

        // If no workspace context, try to derive from the entity if it has BelongsToWorkspace
        if (!$workspaceId && method_exists($entity, 'workspace')) {
            $workspaceId = $entity->workspace_id;
        }

        if (!$workspaceId) {
            // Cannot log an activity without a workspace context
            return null;
        }

        $activity = Activity::create([
            'workspace_id' => $workspaceId,
            'user_id' => auth()->id(), // System tasks will be null
            'event' => $event,
            'entity_type' => get_class($entity),
            'entity_id' => $entity->getKey(),
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'metadata' => array_merge($metadata ?? [], [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]),
        ]);

        $this->dispatchWebhooks($workspaceId, $event, $activity);

        return $activity;
    }

    protected function dispatchWebhooks(int $workspaceId, string $event, Activity $activity)
    {
        // Find webhooks in this workspace that subscribe to this specific event (or all events '*')
        $webhooks = \Modules\CRM\Models\Webhook::where('workspace_id', $workspaceId)
            ->where('is_active', true)
            ->where(function ($query) use ($event) {
                $query->whereJsonContains('events', $event)
                      ->orWhereJsonContains('events', '*');
            })
            ->get();

        if ($webhooks->isEmpty()) {
            return;
        }

        $payload = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'activity_id' => $activity->id,
            'data' => [
                'entity_type' => $activity->entity_type,
                'entity_id' => $activity->entity_id,
                'old_value' => $activity->old_value,
                'new_value' => $activity->new_value,
            ]
        ];

        foreach ($webhooks as $webhook) {
            \Modules\CRM\Jobs\CallWebhookJob::dispatch($webhook, $payload);
        }
    }
}
