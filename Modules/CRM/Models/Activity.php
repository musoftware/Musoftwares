<?php

namespace Modules\CRM\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Activity extends Model
{
    use SoftDeletes;

    protected $table = 'crm_activities';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'event',
        'entity_type',
        'entity_id',
        'old_value',
        'new_value',
        'metadata',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'metadata' => 'array',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entity()
    {
        return $this->morphTo();
    }
}
