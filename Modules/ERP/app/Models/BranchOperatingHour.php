<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;

class BranchOperatingHour extends Model
{
    protected $table = 'erp_branch_operating_hours';
    protected $guarded = [];

    protected $casts = [
        'is_closed' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
