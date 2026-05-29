<?php

namespace Modules\ERP\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray($request)
    {
        $totalItems     = $this->items_count ?? $this->items()->count();
        $completedItems = $this->completed_items_count ?? $this->items()->where('completed', true)->count();
        $progress       = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 0) : null;

        return [
            'id'             => $this->id,
            'task_name'      => $this->task_name,
            'task_description'=> $this->task_description,
            'status'         => $this->status,
            'priority'       => $this->priority,
            'archived'       => $this->archived,
            'due_date'       => $this->due_date?->format('M j, Y'),
            'created_at'     => $this->created_at->format('M j, Y'),
            'client'         => $this->client ? ['id' => $this->client->id, 'name' => $this->client->name] : null,
            'project'        => $this->project ? ['id' => $this->project->id, 'name' => $this->project->name] : null,
            'creator'        => $this->creator?->name,
            'todos_count'    => $totalItems - $completedItems,
            'total_todos'    => $totalItems,
            'completed_todos'=> $completedItems,
            'progress'       => $progress,
        ];
    }
}
