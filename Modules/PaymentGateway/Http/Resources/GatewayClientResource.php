<?php

namespace Modules\PaymentGateway\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GatewayClientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'client_id'         => $this->client_id,
            'client_secret'     => $this->when(
                $request->routeIs('admin.*'),
                $this->client_secret
            ),
            'webhook_secret'    => $this->when(
                $request->routeIs('admin.*'),
                $this->webhook_secret
            ),
            'website'           => $this->website,
            'status'            => $this->status,
            'commission_rate'   => (float) $this->commission_rate,
            'allowed_ips'       => $this->allowed_ips ?? [],
            'stats'             => [
                'total_payments'   => $this->payments()->count(),
                'successful_count' => $this->payments()->where('status', 'success')->count(),
                'total_volume'     => (float) $this->payments()->where('status', 'success')->sum('amount'),
                'total_commission' => (float) $this->payments()->where('status', 'success')->sum('commission_amount'),
            ],
            'created_at'        => $this->created_at?->toDateString(),
        ];
    }
}
