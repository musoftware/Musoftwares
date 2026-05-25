<?php

namespace Modules\AffiliatePos\app\Features\Storefront\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'total_price' => $this->items->sum(fn($item) => $item->price * $item->qty),
            'total_commission' => $this->items->sum(fn($item) => $item->commission * $item->qty),
        ];
    }
}
