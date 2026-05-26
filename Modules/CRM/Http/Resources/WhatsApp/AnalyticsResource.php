<?php

namespace Modules\CRM\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
