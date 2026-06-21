<?php

namespace Modules\CRM\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'workspace_id',
        'branch_id',
        'name',
        'type', // 'text', 'number', 'date', 'select', 'boolean'
        'options', // json array for select options
        'is_required',
        'target_model', // 'lead', 'customer'
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
    ];
}
