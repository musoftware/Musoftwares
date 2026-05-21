<?php

namespace App\Models\CRM;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformLeadSet extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'platform_lead_sets';

    protected $fillable = [
        'name',
        'description',
        'color',
        'created_by',
    ];

    /**
     * Get the user who created this lead set
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the leads in this set
     */
    public function leads()
    {
        return $this->belongsToMany(PlatformLead::class, 'platform_lead_set_memberships', 'lead_set_id', 'lead_id')
            ->withTimestamps();
    }
}
