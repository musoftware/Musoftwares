<?php

namespace Modules\Booking\app\Features\WhiteLabel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhiteLabelAssetsUpdated
{
    use Dispatchable, SerializesModels;

    public int $tenantId;

    public function __construct(int $tenantId)
    {
        $this->tenantId = $tenantId;
    }
}
