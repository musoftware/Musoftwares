<?php

namespace Modules\ERP\Repositories\Approval;

use Modules\ERP\Models\Approval\ApprovalAction;

class ApprovalActionRepository
{
    public function create(array $data)
    {
        return ApprovalAction::create($data);
    }
}
