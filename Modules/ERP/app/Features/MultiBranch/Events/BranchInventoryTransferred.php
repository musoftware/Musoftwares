<?php

namespace Modules\ERP\app\Features\MultiBranch\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\ERP\Models\BranchTransfer;

class BranchInventoryTransferred
{
    use Dispatchable, SerializesModels;

    public $transfer;

    public function __construct(BranchTransfer $transfer)
    {
        $this->transfer = $transfer;
    }
}
