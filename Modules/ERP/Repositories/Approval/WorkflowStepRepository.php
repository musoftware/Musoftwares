<?php

namespace Modules\ERP\Repositories\Approval;

use Modules\ERP\Models\Approval\WorkflowStep;

class WorkflowStepRepository
{
    public function create(array $data)
    {
        return WorkflowStep::create($data);
    }

    public function update(WorkflowStep $step, array $data)
    {
        $step->update($data);
        return $step;
    }

    public function delete(WorkflowStep $step)
    {
        $step->delete();
    }
}
