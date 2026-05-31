<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SequenceState extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sequence_id',
        'assignable_id',
        'assignable_type',
        'status',
        'current_step_order',
        'last_email_sent_at',
    ];

    protected $casts = [
        'last_email_sent_at' => 'datetime',
    ];

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    public function assignable()
    {
        return $this->morphTo();
    }
}
