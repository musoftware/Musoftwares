<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestTicketMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_ticket_id',
        'direction',
        'from_email',
        'to_email',
        'subject',
        'body_html',
        'body_text',
        'message_id',
        'in_reply_to',
        'references',
        'headers_json',
        'attachments_json',
        'sent_at',
        'received_at',
    ];

    protected $casts = [
        'headers_json'     => 'array',
        'attachments_json' => 'array',
        'sent_at'          => 'datetime',
        'received_at'      => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(GuestTicket::class, 'guest_ticket_id');
    }

    public function isInbound(): bool
    {
        return $this->direction === GuestTicket::DIRECTION_INBOUND;
    }

    public function isOutbound(): bool
    {
        return $this->direction === GuestTicket::DIRECTION_OUTBOUND;
    }

    public function attachments(): array
    {
        return $this->attachments_json ?? [];
    }
}
