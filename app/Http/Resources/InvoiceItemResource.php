<?php

namespace App\Http\Resources;

use App\Models\Currency;
use App\Traits\ConvertsCurrency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    use ConvertsCurrency;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_title' => $this->item_title,
            'item_type' => $this->item_type,
            'qty' => $this->qty,
            'amount' => $this->amount,
            'currency' => Currency::find($this->invoice?->currency)?->currency,
            'total_amount' => $this->total(),
            'timers' => $this->whenLoaded('timers', fn () => $this->resource->timers->map(function ($timer) {
                $secs = (int) $timer->diff();
                if ($secs <= 0 && ! empty($timer->date_start) && ! empty($timer->date_end)) {
                    $secs = abs(strtotime($timer->date_end) - strtotime($timer->date_start));
                }
                return [
                    'id' => $timer->id,
                    'date_start' => $timer->date_start,
                    'date_end' => $timer->date_end,
                    'duration_seconds' => $secs,
                    'duration_str' => \App\Helpers\TextHelper::secondsToTime($secs),
                    'amount' => (float) $timer->amount,
                ];
            })),
            'created_at' => $this->created_at,
        ];
    }
}
