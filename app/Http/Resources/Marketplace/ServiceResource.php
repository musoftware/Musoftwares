<?php

namespace App\Http\Resources\Marketplace;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'seller'       => $this->seller ? [
                'id'    => $this->seller->id,
                'name'  => $this->seller->name,
                'email' => $this->seller->email,
            ] : null,
            'category'     => $this->category ? [
                'id'    => $this->category->id,
                'name'  => $this->category->name,
            ] : null,
            'title'        => $this->title,
            'description'  => $this->description,
            'status'       => $this->status,
            'is_featured'  => $this->is_featured,
            'tags'         => $this->tags,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
