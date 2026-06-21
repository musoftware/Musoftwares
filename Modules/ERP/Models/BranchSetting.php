<?php

namespace Modules\ERP\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class BranchSetting extends Model
{
    use SoftDeletes;

    protected $table = 'erp_branch_settings';
    protected $guarded = [];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
