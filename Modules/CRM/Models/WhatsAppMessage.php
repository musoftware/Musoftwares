<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppMessage extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace;

    protected $table = 'crm_whatsapp_messages';

    protected $fillable = [
        'uuid',
        'workspace_id',
        'conversation_id',
        'sender_type',
        'sender_id',
        'whatsapp_message_id',
        'type',
        'body',
        'media_url',
        'media_mime_type',
        'media_size',
        'media_filename',
        'thumbnail_url',
        'quoted_message_id',
        'reaction_emoji',
        'template_name',
        'template_params',
        'delivery_status',
        'failed_reason',
        'is_internal_note',
        'mentions',
        'is_starred',
        'scheduled_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'metadata',
    ];

    protected $casts = [
        'template_params'  => 'array',
        'mentions'         => 'array',
        'metadata'         => 'array',
        'is_internal_note' => 'boolean',
        'is_starred'       => 'boolean',
        'media_size'       => 'integer',
        'scheduled_at'     => 'datetime',
        'sent_at'          => 'datetime',
        'delivered_at'     => 'datetime',
        'read_at'          => 'datetime',
    ];

    // ── Boot ─────────────────────────────────────────────────────

    protected static function booted()
    {
        static::creating(function ($message) {
            if (!$message->uuid) {
                $message->uuid = (string) Str::uuid();
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────

    public function conversation()
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }

    public function quotedMessage()
    {
        return $this->belongsTo(self::class, 'quoted_message_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'quoted_message_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeFromCustomer($query)
    {
        return $query->where('sender_type', 'customer');
    }

    public function scopeFromAgent($query)
    {
        return $query->where('sender_type', 'agent');
    }

    public function scopeInternalNotes($query)
    {
        return $query->where('is_internal_note', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('delivery_status', 'failed');
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')->where('delivery_status', 'pending');
    }

    public function scopeStarred($query)
    {
        return $query->where('is_starred', true);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isFromCustomer(): bool
    {
        return $this->sender_type === 'customer';
    }

    public function isFromAgent(): bool
    {
        return $this->sender_type === 'agent';
    }

    public function isMedia(): bool
    {
        return in_array($this->type, ['image', 'video', 'audio', 'document', 'sticker']);
    }

    public function isFailed(): bool
    {
        return $this->delivery_status === 'failed';
    }

    public function isDelivered(): bool
    {
        return in_array($this->delivery_status, ['delivered', 'read']);
    }

    public function getPreview(int $length = 100): string
    {
        if ($this->is_internal_note) {
            return '📝 Internal note';
        }

        if ($this->isMedia()) {
            return match ($this->type) {
                'image'    => '📷 Photo',
                'video'    => '🎥 Video',
                'audio'    => '🎤 Voice message',
                'document' => '📄 ' . ($this->media_filename ?? 'Document'),
                'sticker'  => '🎨 Sticker',
                default    => '📎 Attachment',
            };
        }

        return Str::limit($this->body ?? '', $length);
    }
}
