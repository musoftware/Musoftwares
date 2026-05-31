<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'category'          => $this->category,
            'original_category' => $this->original_category,
            'title'             => $this->title,
            'content'           => $this->content,
            'admin_id'          => $this->admin_id,
            'is_pinned'         => (bool) $this->is_pinned,
            'author'            => $this->admin ? ['name' => $this->admin->name] : null,
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}
