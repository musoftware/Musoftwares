<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'branch_id',
        'name',
        'type', // 'embed', 'popup', 'floating'
        'pipeline_id', // Target pipeline for leads
        'pipeline_stage_id', // Target stage for leads
        'settings', // json for styling, form fields
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];
}
