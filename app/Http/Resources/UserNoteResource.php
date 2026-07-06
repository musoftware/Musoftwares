<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Support\Carbon;

/**
 * @mixin \App\Models\UserCredential
 */
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
            'is_pinned'         => (bool) $this->is_pinned,
            'author'            => $this->admin ? ['name' => $this->admin->name] : null,
            'rotated_at'        => $this->rotated_at ? Carbon::parse($this->rotated_at)->toISOString() : null,
            'expires_at'        => $this->expires_at ? Carbon::parse($this->expires_at)->toISOString() : null,
            'is_expired'        => $this->expires_at ? Carbon::parse($this->expires_at)->isPast() : false,
            'is_expiring_soon'  => $this->expires_at
                ? Carbon::parse($this->expires_at)->isFuture() && Carbon::parse($this->expires_at)->diffInDays(now()) <= 7
                : false,
            'last_revealed_at'  => $this->last_revealed_at ? Carbon::parse($this->last_revealed_at)->toISOString() : null,
            'created_at'        => $this->created_at ? Carbon::parse($this->created_at)->toISOString() : null,
            'updated_at'        => $this->updated_at ? Carbon::parse($this->updated_at)->toISOString() : null,
        ];
    }
}
