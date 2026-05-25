<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'status'       => $this->status,
            'signed_at'    => $this->signed_at,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'user'         => clone (new UserResource($this->whenLoaded('user'))),
        ];
    }
}
