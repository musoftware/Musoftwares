<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SerialSoftwareResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'default_status' => $this->default_status,
            'total_devices' => $this->total_devices ?? 0,
            'active_count' => $this->active_count ?? 0,
            'inactive_count' => $this->inactive_count ?? 0,
            'blocked_count' => $this->blocked_count ?? 0,
            'created_at' => $this->created_at?->diffForHumans(),
            'created_at_full' => $this->created_at?->toDateTimeString(),
        ];
    }
}
