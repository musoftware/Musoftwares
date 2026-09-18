<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $appends = ['attachments', 'read'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getAttachmentsAttribute(): array
    {
        if (! $this->attachment) {
            return [];
        }

        $extension = strtolower(pathinfo($this->attachment, PATHINFO_EXTENSION));
        $type = 'file';
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
            $type = 'image';
        } elseif (in_array($extension, ['mp3', 'wav', 'ogg', 'm4a', 'webm', 'aac'])) {
            $type = 'audio';
        } elseif (in_array($extension, ['mp4', 'webm', 'mov', 'm4v', 'ogv'])) {
            $type = 'video';
        }

        return [
            [
                'id' => $this->id,
                'type' => $type,
                'path' => asset('storage/'.$this->attachment),
                'name' => basename($this->attachment),
            ],
        ];
    }

    public function getReadAttribute(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return true;
        }

        if ($this->sender_id === $user->id) {
            return true;
        }

        $participant = ConversationParticipant::where('conversation_id', $this->conversation_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $participant || ! $participant->last_read_at) {
            return false;
        }

        return $this->created_at <= $participant->last_read_at;
    }
}
