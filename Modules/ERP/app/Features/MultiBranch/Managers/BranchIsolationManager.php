<?php

namespace Modules\ERP\app\Features\MultiBranch\Managers;

use Illuminate\Support\Facades\Session;

class BranchIsolationManager
{
    protected ?int $activeBranchId = null;

    public function __construct()
    {
        // Try to initialize from session/request if available
        if (request()->hasHeader('X-Branch-ID')) {
            $this->activeBranchId = request()->header('X-Branch-ID');
        } elseif (Session::has('active_branch_id')) {
            $this->activeBranchId = Session::get('active_branch_id');
        }
    }

    public function setActiveBranchId(?int $branchId): void
    {
        $this->activeBranchId = $branchId;
        
        if ($branchId) {
            Session::put('active_branch_id', $branchId);
        } else {
            Session::forget('active_branch_id');
        }
    }

    public function getActiveBranchId(): ?int
    {
        return $this->activeBranchId;
    }

    public function hasActiveBranch(): bool
    {
        return !is_null($this->activeBranchId);
    }
}
