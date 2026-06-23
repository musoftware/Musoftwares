<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchTransfer extends Model
{
    use SoftDeletes;
    
    protected $table = 'erp_branch_transfers';
    protected $guarded = [];

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(\Modules\ERP\Models\TeamMember::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\Modules\ERP\Models\TeamMember::class, 'approved_by');
    }
}
