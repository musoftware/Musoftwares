<?php

namespace App\Http\Resources\Tools;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToolResellerUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'user_id'               => $this->user_id,
            'user'                  => $this->whenLoaded('user', fn() => ['id' => $this->user->id, 'name' => $this->user->name, 'email' => $this->user->email]),
            'status'                => $this->status,
            'sharing_check_enabled' => $this->sharing_check_enabled,
            'is_sharing_flagged'    => $this->is_sharing_flagged,
            'flagged_ips'           => $this->flagged_ips,
            'sharing_flagged_at'    => $this->sharing_flagged_at?->diffForHumans(),
            'joined_at'             => $this->joined_at?->toDateString(),
        ];
    }
}
