<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SequenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'trigger_type' => $this->trigger_type,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'steps_count' => $this->whenCounted('steps'),
            'states_count' => $this->whenCounted('states'),
            'steps' => $this->whenLoaded('steps'),
        ];
    }
}
