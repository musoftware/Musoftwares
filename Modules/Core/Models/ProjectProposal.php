<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_name',
        'project_details',
        'total_cost_egp',
        'total_duration_days',
        'cost_breakdown',
        'proposal_data',
        'ascii_table',
        'adjustment_type',
        'adjustment_value',
    ];

    protected $casts = [
        'cost_breakdown' => 'array',
        'proposal_data' => 'array',
        'total_cost_egp' => 'decimal:2',
        'total_duration_days' => 'decimal:2',
        'adjustment_value' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
