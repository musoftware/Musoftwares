<?php

namespace Modules\ERP\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientSearchResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'currency_code' => $this->currency?->currency,
        ];
    }
}
