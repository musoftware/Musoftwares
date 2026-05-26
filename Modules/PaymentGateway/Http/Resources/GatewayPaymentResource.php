<?php

namespace Modules\PaymentGateway\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GatewayPaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->id,
            'internal_order_id'      => $this->internal_order_id,
            'external_order_id'      => $this->external_order_id,
            'amount'                 => (float) $this->amount,
            'currency'               => $this->currency,
            'commission_rate'        => (float) $this->commission_rate,
            'commission_amount'      => (float) $this->commission_amount,
            'net_amount'             => (float) $this->net_amount,
            'description'            => $this->description,
            'customer_name'          => $this->customer_name,
            'customer_email'         => $this->customer_email,
            'status'                 => $this->status,
            'status_badge'           => $this->status_badge,
            'kashier_transaction_id' => $this->kashier_transaction_id,
            'kashier_payment_url'    => $this->when(
                $this->status === 'pending',
                $this->kashier_payment_url
            ),
            'client'                 => $this->whenLoaded('client', fn() => [
                'id'   => $this->client->id,
                'name' => $this->client->name,
            ]),
            'created_at'             => $this->created_at?->toDateTimeString(),
        ];
    }
}
