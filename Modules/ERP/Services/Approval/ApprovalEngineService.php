<?php

namespace Modules\ERP\Services\Approval;

use Modules\ERP\Repositories\Approval\ApprovalRequestRepository;
use Modules\ERP\Repositories\Approval\ApprovalActionRepository;
use Modules\ERP\Repositories\Approval\EscalationRepository;
use Modules\ERP\Models\Approval\WorkflowDefinition;
use Illuminate\Support\Facades\DB;
use Exception;

class ApprovalEngineService
{
    public function __construct(
        protected ApprovalRequestRepository $requestRepo,
        protected ApprovalActionRepository $actionRepo,
        protected EscalationRepository $escalationRepo
    ) {}

    public function createRequest(WorkflowDefinition $definition, $approvable, int $requesterId)
    {
        return $this->requestRepo->create([
            'tenant_id' => $definition->tenant_id,
            'workflow_definition_id' => $definition->id,
            'approvable_type' => get_class($approvable),
            'approvable_id' => $approvable->id,
            'status' => 'pending',
            'current_step_order' => 1,
            'requester_id' => $requesterId,
        ]);
    }

    public function processAction(string $requestId, int $approverId, string $actionType, ?string $comments = null)
    {
        DB::beginTransaction();
        try {
            $request = $this->requestRepo->findById($requestId);
            
            if ($request->status !== 'pending') {
                throw new Exception("Approval request is already {$request->status}");
            }

            $currentStep = $request->workflowDefinition->steps()->where('order', $request->current_step_order)->first();
            
            if (!$currentStep) {
                throw new Exception("No workflow step found");
            }

            // Create action record
            $this->actionRepo->create([
                'approval_request_id' => $request->id,
                'workflow_step_id' => $currentStep->id,
                'approver_id' => $approverId,
                'action' => $actionType, // 'approved', 'rejected'
                'comments' => $comments,
            ]);

            if ($actionType === 'rejected') {
                $this->requestRepo->update($request, ['status' => 'rejected']);
                DB::commit();
                return $request;
            }

            // Handle approval
            // Check if requires_all logic applies here (simplified for this structure)
            // Assuming single approval moves to next step for now
            $nextStep = $request->workflowDefinition->steps()->where('order', '>', $request->current_step_order)->orderBy('order')->first();

            if ($nextStep) {
                $this->requestRepo->update($request, ['current_step_order' => $nextStep->order]);
            } else {
                $this->requestRepo->update($request, ['status' => 'approved']);
            }

            DB::commit();
            return $request;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function escalateRequest(string $requestId, int $escalatedToId, string $reason)
    {
        $request = $this->requestRepo->findById($requestId);
        $currentStep = $request->workflowDefinition->steps()->where('order', $request->current_step_order)->first();

        return $this->escalationRepo->create([
            'approval_request_id' => $request->id,
            'workflow_step_id' => $currentStep->id ?? null,
            'escalated_to_id' => $escalatedToId,
            'escalated_to_type' => 'user',
            'reason' => $reason,
        ]);
    }
}
