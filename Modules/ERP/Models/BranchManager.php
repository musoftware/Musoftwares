<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;

class BranchManager extends Model
{
    protected $table = 'erp_branch_managers';
    protected $guarded = [];

    protected $casts = [
        'assigned_at' => 'datetime',
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
