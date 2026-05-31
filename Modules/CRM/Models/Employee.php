<?php

namespace Modules\CRM\Models;

use App\Models\User;

class Employee extends User
{
    protected $table = 'users';

    public function leads()
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'assigned_to');
    }

    // Branch relationship if exists
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
