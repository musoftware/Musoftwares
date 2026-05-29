<?php

namespace Modules\ERP\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->action,
            'time' => $this->created_at?->diffForHumans(),
            'description' => $this->description,
            'user' => $this->causer?->name ?? 'System',
        ];
    }
}
