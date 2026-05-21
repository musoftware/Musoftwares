<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformLead extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'platform_leads';

    protected $fillable = [
        'name',
        'email',
        'company',
        'message',
        'status',
        'locale',
        'phone',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the lead sets this lead belongs to
     */
    public function leadSets()
    {
        return $this->belongsToMany(PlatformLeadSet::class, 'platform_lead_set_memberships', 'lead_id', 'lead_set_id')
            ->withTimestamps();
    }
}
