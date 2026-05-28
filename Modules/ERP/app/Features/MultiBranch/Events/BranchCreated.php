<?php

namespace Modules\ERP\app\Features\MultiBranch\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\ERP\Models\Branch;

class BranchCreated
{
    use Dispatchable, SerializesModels;

    public $branch;

    public function __construct(Branch $branch)
    {
        $this->branch = $branch;
    }
}
