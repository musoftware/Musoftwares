<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FreeDownloadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'description'          => $this->description,
            'programming_language' => $this->programming_language,
            'image'                => $this->image ? asset('storage/' . $this->image) : null,
            'file_path'            => $this->file_path ? asset('storage/' . $this->file_path) : null,
            'original_filename'    => $this->original_filename,
            'is_active'            => (bool) $this->is_active,
            'order_column'         => $this->order_column,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
