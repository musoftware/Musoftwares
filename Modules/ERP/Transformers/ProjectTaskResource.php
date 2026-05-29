<?php

namespace Modules\ERP\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectTaskResource extends JsonResource
{
    public function toArray($request)
    {
        $category = 'Todo';
        if ($this->status === 'in_progress') $category = 'In Progress';
        if ($this->status === 'review') $category = 'In Review';
        if ($this->status === 'completed') $category = 'Done';

        return [
            'id' => $this->id,
            'title' => $this->task_name,
            'due' => $this->due_date ? $this->due_date->format('M j, Y') : 'No due date',
            'assignee' => $this->assignee ? $this->assignee->name : ($this->creator ? $this->creator->name : 'Unassigned'),
            'priority' => ucfirst($this->priority ?? 'Normal'),
            'category' => $category,
        ];
    }
}
