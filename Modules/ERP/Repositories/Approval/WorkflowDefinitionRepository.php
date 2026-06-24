<?php

namespace Modules\ERP\Repositories\Approval;

use Modules\ERP\Models\Approval\WorkflowDefinition;

class WorkflowDefinitionRepository
{
    public function getAll()
    {
        return WorkflowDefinition::with('steps')->get();
    }

    public function findById(string $id)
    {
        return WorkflowDefinition::with('steps')->findOrFail($id);
    }

    public function create(array $data)
    {
        return WorkflowDefinition::create($data);
    }

    public function update(WorkflowDefinition $definition, array $data)
    {
        $definition->update($data);
        return $definition;
    }

    public function delete(WorkflowDefinition $definition)
    {
        $definition->delete();
    }
}
