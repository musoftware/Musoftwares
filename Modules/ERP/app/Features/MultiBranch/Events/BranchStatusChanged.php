<?php

namespace Modules\ERP\app\Features\MultiBranch\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\ERP\Models\Branch;

class BranchStatusChanged
{
    use Dispatchable, SerializesModels;

    public $branch;
    public $oldStatus;
    public $newStatus;

    public function __construct(Branch $branch, string $oldStatus, string $newStatus)
    {
        $this->branch = $branch;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}
