<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GoldWatchlistResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'market_keys'         => $this->market_keys ?? [],
            'tracked_karats'      => $this->tracked_karats ?? [],
            'tracked_currencies'  => $this->tracked_currencies ?? [],
            'is_default'          => $this->is_default,
            'created_at'          => $this->created_at?->toISOString(),
        ];
    }
}
