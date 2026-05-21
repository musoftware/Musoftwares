<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformSequenceState extends Model
{
    use HasFactory;

    protected $table = 'platform_sequence_states';

    protected $fillable = [
        'sequence_id',
        'assignable_id',
        'assignable_type',
        'status',
        'current_step_order',
        'next_action_at',
    ];

    protected $casts = [
        'next_action_at' => 'datetime',
    ];

    public function sequence()
    {
        return $this->belongsTo(PlatformSequence::class, 'sequence_id');
    }

    public function assignable()
    {
        return $this->morphTo();
    }
}
