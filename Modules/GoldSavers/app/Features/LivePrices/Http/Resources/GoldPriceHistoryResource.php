<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GoldPriceHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'period_start' => $this->period_start?->toISOString(),
            'period_end'   => $this->period_end?->toISOString(),
            'open'         => $this->open_price,
            'high'         => $this->high_price,
            'low'          => $this->low_price,
            'close'        => $this->close_price,
            'avg'          => $this->avg_price,
            'ticks'        => $this->tick_count,
            'interval'     => $this->interval,
            'karat'        => $this->karat,
            'currency'     => $this->currency,
        ];
    }
}
