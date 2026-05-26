<?php

namespace Modules\CRM\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'phone_number'  => $this->phone_number,
            'provider'      => $this->provider,
            'status'        => $this->status,
            'is_default'    => $this->is_default,
            'last_seen_at'  => $this->last_seen_at?->toIso8601String(),
            'health_status' => $this->health_status,
            'created_at'    => $this->created_at->toIso8601String(),

            'assigned_user' => $this->whenLoaded('assignedUser', fn() => [
                'id'    => $this->assignedUser->id,
                'name'  => $this->assignedUser->name,
                'email' => $this->assignedUser->email,
            ]),
        ];
    }
}
