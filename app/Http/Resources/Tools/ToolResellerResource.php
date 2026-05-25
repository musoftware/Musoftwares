<?php

namespace App\Http\Resources\Tools;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToolResellerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'token'               => $this->token,
            'balance'             => (float) $this->balance,
            'currency'            => $this->currency,
            'status'              => $this->status,
            'notes'               => $this->notes,
            'user'                => $this->whenLoaded('user', fn() => ['id' => $this->user->id, 'name' => $this->user->name, 'email' => $this->user->email]),
            'total_users'         => $this->reseller_users_count ?? 0,
            'active_users'        => $this->active_users_count ?? 0,
            'sharing_flagged'     => $this->sharing_flagged_count ?? 0,
            'created_at'          => $this->created_at?->toDateString(),
        ];
    }
}
