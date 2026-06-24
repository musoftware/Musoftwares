<?php

namespace Modules\ERP\Services\Approval;

use Modules\ERP\Repositories\Approval\WorkflowDefinitionRepository;
use Modules\ERP\Repositories\Approval\WorkflowStepRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class WorkflowDefinitionService
{
    public function __construct(
        protected WorkflowDefinitionRepository $definitionRepo,
        protected WorkflowStepRepository $stepRepo
    ) {}

    public function createDefinitionWithSteps(array $data)
    {
        DB::beginTransaction();
        try {
            $definition = $this->definitionRepo->create([
                'tenant_id' => $data['tenant_id'] ?? null, // Handled by scope usually, but good to pass
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'module_type' => $data['module_type'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (!empty($data['steps'])) {
                foreach ($data['steps'] as $index => $stepData) {
                    $this->stepRepo->create([
                        'workflow_definition_id' => $definition->id,
                        'name' => $stepData['name'],
                        'order' => $stepData['order'] ?? ($index + 1),
                        'approver_type' => $stepData['approver_type'],
                        'approver_id' => $stepData['approver_id'] ?? null,
                        'requires_all' => $stepData['requires_all'] ?? false,
                    ]);
                }
            }

            DB::commit();
            return $this->definitionRepo->findById($definition->id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateDefinition(string $id, array $data)
    {
        $definition = $this->definitionRepo->findById($id);
        return $this->definitionRepo->update($definition, $data);
    }
}
