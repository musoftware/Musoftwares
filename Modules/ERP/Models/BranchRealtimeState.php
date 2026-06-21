<?php

namespace Modules\ERP\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class BranchRealtimeState extends Model
{
    use SoftDeletes;

    protected $table = 'erp_branch_realtime_states';
    protected $guarded = [];

    protected $casts = [
        'last_updated_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
