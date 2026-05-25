<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GoldLivePriceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'market_key'      => $this->market_key,
            'price_24k'       => $this->price_gram_24k,
            'price_21k'       => $this->price_gram_21k,
            'price_18k'       => $this->price_gram_18k,
            'price_14k'       => $this->price_gram_14k,
            'buy_price'       => $this->buy_price,
            'sell_price'      => $this->sell_price,
            'spread'          => $this->spread,
            'price_usd_oz'    => $this->price_usd_oz,
            'currency'        => $this->currency,
            'exchange_rate'   => $this->exchange_rate,
            'price_delta'     => $this->price_delta,
            'price_delta_pct' => $this->price_delta_pct,
            'direction'       => $this->direction,
            'is_stale'        => $this->is_stale,
            'stale_since'     => $this->stale_since?->toISOString(),
            'fetched_at'      => $this->fetched_at?->toISOString(),
            'broadcasted_at'  => $this->broadcasted_at?->toISOString(),
            'source'          => $this->when($this->relationLoaded('source'), fn () => [
                'name'   => $this->source?->name,
                'driver' => $this->source?->driver,
            ]),
        ];
    }
}
