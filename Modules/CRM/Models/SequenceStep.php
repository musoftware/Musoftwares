<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SequenceStep extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sequence_id',
        'delay',
        'unit',
        'order',
        'send_email',
        'email_subject',
        'email_content',
    ];

    protected $casts = [
        'email_subject' => 'array',
        'email_content' => 'array',
        'send_email' => 'boolean',
    ];

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }
}
