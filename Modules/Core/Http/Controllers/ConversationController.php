<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\Conversation;
use Modules\Core\Models\Message;
use Modules\Core\Models\ConversationParticipant;
use Illuminate\Support\Facades\Gate;

class ConversationController extends Controller
{
    /**
     * Get a listing of conversations for the authenticated user.
     */
    public function index()
    {
        $conversations = Conversation::with(['participants.user', 'messages' => function($q) {
                $q->latest()->take(1);
            }])
            ->whereHas('participants', function($q) {
                $q->where('user_id', auth()->id());
            })
            ->get();

        return response()->json($conversations);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $this->authorizeParticipant($id);

        $conversation = Conversation::with(['messages.attachments', 'participants.user'])->findOrFail($id);
        return response()->json($conversation);
    }

    /**
     * Get paginated messages for a conversation.
     */
    public function messages($id)
    {
        $this->authorizeParticipant($id);

        $messages = Message::with(['attachments', 'sender'])
            ->where('conversation_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($messages);
    }

    /**
     * Mark conversation as read for the current user.
     */
    public function markAsRead($id)
    {
        $this->authorizeParticipant($id);

        ConversationParticipant::where('conversation_id', $id)
            ->where('user_id', auth()->id())
            ->update(['last_read_at' => now()]);

        return response()->json(['status' => 'success']);
    }

    /**
     * Ensure the authenticated user is a participant.
     */
    private function authorizeParticipant($conversationId)
    {
        $isParticipant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', auth()->id())
            ->exists();

        if (!$isParticipant) {
            abort(403, 'Unauthorized access to conversation.');
        }
    }
}
