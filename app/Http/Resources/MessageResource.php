<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'body'       => $this->body,
            'attachment' => $this->attachment,
            'is_system'  => (bool) $this->is_system,
            'sender'     => $this->whenLoaded('sender', function () {
                return [
                    'id'     => $this->sender->id,
                    'name'   => $this->sender->name,
                    'email'  => $this->sender->email,
                    'avatar' => $this->sender->avatar ?? null,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
