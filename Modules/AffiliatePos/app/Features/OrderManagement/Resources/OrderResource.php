<?php

namespace Modules\AffiliatePos\app\Features\OrderManagement\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'unique_id' => $this->unique_id,
            'status' => $this->status,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'total' => $this->total,
            'delivery' => $this->delivery,
            'subtotal' => $this->subtotal,
            'created_at' => $this->created_at,
            'items' => $this->whenLoaded('items', function() {
                return $this->items->map(fn($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product->name ?? '',
                    'sku_title' => $item->sku->title ?? '',
                    'qty' => $item->qty,
                    'price' => $item->price,
                    'total' => $item->total,
                    'commission' => $item->total_commission,
                    'status' => $item->status,
                ]);
            })
        ];
    }
}
