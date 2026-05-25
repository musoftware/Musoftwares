<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $table = 'crm_webhooks';

    protected $fillable = [
        'workspace_id',
        'name',
        'url',
        'events',
        'secret',
        'is_active',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
