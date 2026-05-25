<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'user_id'         => $this->user_id,
            'project_name'    => $this->project_name,
            'project_balance' => (float) $this->project_balance,
            'status'          => $this->status,
            'archived'        => (bool) $this->archived,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'client'          => $this->whenLoaded('client', fn() => [
                'id'    => $this->client->id,
                'name'  => $this->client->name,
                'email' => $this->client->email,
            ]),
        ];
    }
}
