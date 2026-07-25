<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Marketplace\Models\ServiceOrder
 */
class MarketplaceOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currencyStr = $this->currency ? $this->currency->currency : 'USD';

        return [
            'id' => $this->id,
            'buyer_id' => $this->buyer_id,
            'seller_id' => $this->seller_id,
            'package_id' => $this->package_id,
            'status' => $this->status,
            'amount' => (float) $this->amount,
            'commission_amount' => (float) ($this->commission_amount ?? 0),
            'seller_earnings' => (float) max(0, $this->amount - ($this->commission_amount ?? 0)),
            'currency' => $currencyStr,
            'notes' => $this->notes,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'buyer' => $this->whenLoaded('buyer', fn () => clone (new UserResource($this->buyer))),
            'seller' => $this->whenLoaded('seller', fn () => clone (new UserResource($this->seller))),
            'package' => $this->whenLoaded('package'),
            'escrow' => $this->whenLoaded('escrow'),
            'delivery_files' => $this->whenLoaded('deliveryFiles'),
            'messages' => $this->whenLoaded('conversation', function () {
                if (!$this->conversation || !$this->conversation->relationLoaded('messages')) {
                    return [];
                }
                return $this->conversation->messages->map(fn ($msg) => [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'sender_name' => $msg->sender ? $msg->sender->name : null,
                    'body' => $msg->body,
                    'created_at' => $msg->created_at ? $msg->created_at->toIso8601String() : null,
                ]);
            }),
        ];
    }
}
