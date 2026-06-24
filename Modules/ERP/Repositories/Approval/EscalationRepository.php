<?php

namespace Modules\ERP\Repositories\Approval;

use Modules\ERP\Models\Approval\Escalation;

class EscalationRepository
{
    public function create(array $data)
    {
        return Escalation::create($data);
    }

    public function update(Escalation $escalation, array $data)
    {
        $escalation->update($data);
        return $escalation;
    }
}
