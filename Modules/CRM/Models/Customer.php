<?php

namespace Modules\CRM\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'branch_id',
        'lead_id', // original lead reference
        'name',
        'email',
        'phone',
        'company',
        'total_value',
        'custom_data',
        'assigned_to',
    ];

    protected $casts = [
        'custom_data' => 'array',
        'total_value' => 'decimal:2',
    ];

    public function originalLead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }
}
