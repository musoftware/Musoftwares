<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'buyer_id'     => $this->buyer_id,
            'seller_id'    => $this->seller_id,
            'package_id'   => $this->package_id,
            'status'       => $this->status,
            'amount'       => (float) $this->amount,
            'completed_at' => $this->completed_at,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'buyer'        => $this->whenLoaded('buyer', fn() => clone (new UserResource($this->buyer))),
            'seller'       => $this->whenLoaded('seller', fn() => clone (new UserResource($this->seller))),
            'package'      => $this->whenLoaded('package'),
        ];
    }
}
