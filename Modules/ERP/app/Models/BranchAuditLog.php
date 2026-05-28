<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;

class BranchAuditLog extends Model
{
    protected $table = 'erp_branch_audit_logs';
    protected $guarded = [];

    protected $casts = [
        'meta' => 'json',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
