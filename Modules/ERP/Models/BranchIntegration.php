<?php

namespace Modules\ERP\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class BranchIntegration extends Model
{
    use SoftDeletes;

    protected $table = 'erp_branch_integrations';
    protected $guarded = [];

    protected $casts = [
        'credentials' => 'json',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
