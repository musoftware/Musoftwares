<?php

namespace Modules\ERP\app\Features\MultiBranch\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\ERP\Models\BranchManager;

class BranchManagerAssigned
{
    use Dispatchable, SerializesModels;

    public $manager;

    public function __construct(BranchManager $manager)
    {
        $this->manager = $manager;
    }
}
