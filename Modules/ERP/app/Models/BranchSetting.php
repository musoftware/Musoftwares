<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;

class BranchSetting extends Model
{
    protected $table = 'erp_branch_settings';
    protected $guarded = [];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
