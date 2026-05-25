<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SerialSoftwareResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'default_status' => $this->default_status,
            'total_licenses' => $this->devices_count ?? 0,
            'active_count'   => $this->devices()->where('status', 'active')->count(),
            'inactive_count' => $this->devices()->where('status', 'inactive')->count(),
            'created_at'     => $this->created_at?->diffForHumans(),
        ];
    }
}
