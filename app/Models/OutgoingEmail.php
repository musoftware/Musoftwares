<?php

namespace App\Models;

use App\Models\Concerns\ScopesToLegalWindow;

class OutgoingEmail extends BaseModel
{
    use ScopesToLegalWindow;

    protected $table = 'outgoing_emails';

    protected $fillable = [
        'to_email',
        'subject',
        'mail_class',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSentToday($query)
    {
        $todayStart = now('Africa/Cairo')->startOfDay()->setTimezone('UTC');
        $todayEnd = now('Africa/Cairo')->endOfDay()->setTimezone('UTC');

        return $query->whereBetween('sent_at', [$todayStart, $todayEnd]);
    }
}
