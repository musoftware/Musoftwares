<?php

namespace Modules\Booking\app\Features\WhiteLabel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelDomain;

class WhiteLabelDomainConnected
{
    use Dispatchable, SerializesModels;

    public WhiteLabelDomain $domain;

    public function __construct(WhiteLabelDomain $domain)
    {
        $this->domain = $domain;
    }
}
