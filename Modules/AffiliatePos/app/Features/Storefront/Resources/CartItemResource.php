<?php

namespace Modules\AffiliatePos\app\Features\Storefront\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'cart_id' => $this->cart_id,
            'sku_id' => $this->sku_id,
            'product_id' => $this->product_id,
            'qty' => $this->qty,
            'price' => $this->price,
            'commission' => $this->commission,
            'product' => [
                'name' => $this->product->name ?? '',
                'sku_title' => $this->sku->title ?? '',
            ]
        ];
    }
}
