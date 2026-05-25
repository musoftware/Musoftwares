<?php

namespace App\Modules\BookingPriority\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingPriorityLevel extends Model
{
    use SoftDeletes;

    protected $table = 'booking_priority_levels';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'weight',
        'color',
        'icon',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(BookingPriorityAssignment::class, 'priority_level_id');
    }
}
