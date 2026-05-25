<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class LeadTag extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $fillable = [
        'workspace_id',
        'name',
        'color',
    ];

    public function leads()
    {
        return $this->belongsToMany(Lead::class, 'lead_tag', 'tag_id', 'lead_id');
    }
}
