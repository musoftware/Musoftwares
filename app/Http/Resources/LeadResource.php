<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'company'      => $this->company,
            'source'       => $this->source,
            'status'       => $this->status,
            'custom_data'  => $this->custom_data,
            'assignee'   => $this->whenLoaded('assignee', function () {
                return ['id' => $this->assignee->id, 'name' => $this->assignee->name];
            }),
            'tags'         => $this->whenLoaded('tags'),
            'notes'        => $this->whenLoaded('notes', function () {
                return $this->notes->map(fn($n) => [
                    'id' => $n->id,
                    'note' => $n->note,
                    'is_pinned' => $n->is_pinned,
                    'created_at' => $n->created_at,
                    'author' => ['id' => $n->author->id ?? null, 'name' => $n->author->name ?? 'System']
                ]);
            }),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
