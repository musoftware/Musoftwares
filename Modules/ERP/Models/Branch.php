<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $table = 'erp_branches';
    protected $guarded = [];

    public function settings()
    {
        return $this->hasMany(BranchSetting::class);
    }

    public function managers()
    {
        return $this->hasMany(BranchManager::class);
    }

    public function operatingHours()
    {
        return $this->hasMany(BranchOperatingHour::class);
    }

    public function integrations()
    {
        return $this->hasMany(BranchIntegration::class);
    }

    public function realtimeStates()
    {
        return $this->hasMany(BranchRealtimeState::class);
    }
}
