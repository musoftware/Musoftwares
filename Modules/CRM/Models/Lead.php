<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CleansLeadData;

class Lead extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes, CleansLeadData;

    protected $fillable = [
        'user_id',
        'campaign_id',
        'source',
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
     * Get the campaigns for this lead
     */
    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_lead')
            ->withTimestamps()
            ->withPivot('status', 'sent_at', 'failed_reason');
    }

    /**
     * Get the lead sets this lead belongs to
     */
    public function leadSets()
    {
        return $this->belongsToMany(LeadSet::class, 'lead_set_memberships')
            ->withTimestamps()
            ->withPivot('id');
    }
}
