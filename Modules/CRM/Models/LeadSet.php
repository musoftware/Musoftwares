<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadSet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'color',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
        return $this->belongsToMany(Lead::class, 'lead_set_memberships')
            ->withTimestamps()
            ->withPivot('id');
    }

    /**
     * Get the count of leads in this set
     */
    public function getLeadsCountAttribute()
    {
        return $this->leads()->count();
    }

    /**
     * Scope to get sets created by the authenticated user
     */
    public function scopeMine($query)
    {
        return $query->where('created_by', auth()->id());
    }

    /**
     * Scope to get sets with lead count
     */
    public function scopeWithLeadCount($query)
    {
        return $query->withCount('leads');
    }
}
