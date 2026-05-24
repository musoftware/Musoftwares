<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
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
        if (!$this->attachment) {
            return [];
        }
        return [
            [
                'id' => $this->id,
                'type' => 'image',
                'path' => asset('storage/' . $this->attachment),
            ]
        ];
    }

    public function getReadAttribute(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return true;
        }

        if ($this->sender_id === $user->id) {
            return true;
        }

        $participant = ConversationParticipant::where('conversation_id', $this->conversation_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$participant || !$participant->last_read_at) {
            return false;
        }

        return $this->created_at <= $participant->last_read_at;
    }
}
