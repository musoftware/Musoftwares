<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'module'    => $this->module,
            'name'      => $this->name,
            'price'     => (float) $this->price,
            'billing'   => $this->billing,
            'features'  => $this->features,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
