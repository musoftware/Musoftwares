<?php

namespace Modules\ERP\Repositories\Approval;

use Modules\ERP\Models\Approval\ApprovalRequest;

class ApprovalRequestRepository
{
    public function getAll()
    {
        return ApprovalRequest::with(['workflowDefinition', 'actions', 'escalations'])->get();
    }

    public function findById(string $id)
    {
        return ApprovalRequest::with(['workflowDefinition', 'actions', 'escalations'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return ApprovalRequest::create($data);
    }

    public function update(ApprovalRequest $request, array $data)
    {
        $request->update($data);
        return $request;
    }

    public function delete(ApprovalRequest $request)
    {
        $request->delete();
    }
}
