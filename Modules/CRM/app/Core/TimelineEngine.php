<?php

namespace Modules\CRM\app\Core;

use Modules\CRM\Models\Activity;
use Illuminate\Database\Eloquent\Model;

class TimelineEngine
{
    /**
     * Get the unified timeline feed for an entity.
     *
     * @param Model $entity The eloquent model (e.g. Lead, Task)
     * @return \Illuminate\Support\Collection
     */
    public function getFeed(Model $entity)
    {
        $activities = Activity::where('entity_type', get_class($entity))
            ->where('entity_id', $entity->getKey())
            ->with('user:id,name,email,profile_photo_path')
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform the raw activities into structured Timeline Events
        return $activities->map(function ($activity) {
            return $this->formatEvent($activity);
        });
    }

    protected function formatEvent(Activity $activity): array
    {
        $baseEvent = [
            'id' => $activity->id,
            'event' => $activity->event,
            'user' => $activity->user ? [
                'id' => $activity->user->id,
                'name' => $activity->user->name,
                'avatar' => $activity->user->profile_photo_path,
            ] : null,
            'created_at' => $activity->created_at,
            'created_at_human' => $activity->created_at->diffForHumans(),
            'metadata' => $activity->metadata,
        ];

        // Specific formatting based on event type
        switch ($activity->event) {
            case 'lead.stage_changed':
                $baseEvent['description'] = 'Changed the status';
                $baseEvent['old_value'] = $activity->old_value['status'] ?? 'Unknown';
                $baseEvent['new_value'] = $activity->new_value['status'] ?? 'Unknown';
                break;
                
            case 'lead.assigned':
                $baseEvent['description'] = 'Assigned the lead';
                // Here we might need to resolve user names if we only have IDs
                break;

            case 'note.created':
                $baseEvent['description'] = 'Added a note';
                $baseEvent['content'] = $activity->new_value['note'] ?? '';
                $baseEvent['is_pinned'] = $activity->new_value['is_pinned'] ?? false;
                break;

            case 'lead.created':
                $baseEvent['description'] = 'Created this lead';
                break;
                
            default:
                $baseEvent['description'] = "Performed action: {$activity->event}";
                break;
        }

        return $baseEvent;
    }
}
