<?php

namespace Modules\Core\Models;

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
        'send_whatsapp',
        'email_subject',
        'email_content',
        'whatsapp_content',
    ];

    protected $casts = [
        'email_subject' => 'array',
        'email_content' => 'array',
        'whatsapp_content' => 'array',
        'send_email' => 'boolean',
        'send_whatsapp' => 'boolean',
    ];

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }
}
