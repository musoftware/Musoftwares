<?php

namespace Modules\ERP\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class TodoItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'description'    => $this->description,
            'completed'      => $this->completed,
            'completed_at'   => $this->completed_at?->toISOString(),
            'priority'       => $this->priority,
            'priority_color' => $this->priority_color,
            'sort_index'     => $this->sort_index,
            'paused'         => $this->paused,
            'is_paid'        => $this->is_paid,
            'cost'           => $this->cost,
            'cost_currency'  => $this->cost_currency,
            'start_at'       => $this->start_at?->toISOString(),
            'end_at'         => $this->end_at?->toISOString(),
            'tags'           => $this->tags ?? [],
            'parent_id'      => $this->parent_id,
            'children'       => $this->relationLoaded('children')
                ? TodoItemResource::collection($this->children)->resolve()
                : [],
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}
