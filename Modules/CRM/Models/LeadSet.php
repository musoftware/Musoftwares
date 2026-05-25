<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Builder;

class LeadSet extends Model
{
    use HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();
        
        static::addGlobalScope('creator', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('created_by', auth()->id());
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });
    }

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
