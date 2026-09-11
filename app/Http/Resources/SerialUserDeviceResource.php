<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SerialUserDevice
 * @property \App\Models\SerialUserDevice $resource
 */
class SerialUserDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'device_id' => $this->device_id,
            'status' => $this->status,
            'expires_at' => $this->resource->expires_at?->toIso8601String(),
            'expires_at_formatted' => $this->resource->expires_at?->toDateString(),
            'is_expired' => $this->resource->isExpired(),
            'remaining_days' => $this->resource->expires_at ? max(0, (int) ceil(now()->diffInDays($this->resource->expires_at, false))) : null,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => clone (new UserResource($this->whenLoaded('user'))),
        ];
    }
}
