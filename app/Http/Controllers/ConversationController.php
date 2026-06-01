<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Storage;

class ConversationController extends Controller
{
    /**
     * Helper to resolve conversation by ID or ticket ID.
     */
    private function resolveConversation($id): Conversation
    {
        $conversation = Conversation::find($id);

        if (!$conversation) {
            // Fallback: Check if $id refers to the ticket ID
            $conversation = Conversation::where('conversable_type', \App\Models\Ticket::class)
                ->where('conversable_id', $id)
                ->first();
        }

        if (!$conversation) {
            abort(404, __('general.conversation_not_found'));
        }

        return $conversation;
    }

    /**
     * Fetch messages for a conversation.
     */
    public function messages(Request $request, $id)
    {
        $user = $request->user();
        $conversation = $this->resolveConversation($id);

        // Security check: user must be a participant or admin
        $isParticipant = $conversation->participants()->where('user_id', $user->id)->exists();
        if (!$isParticipant && !$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        // Return messages sorted descending (newest first) as ChatWindow.jsx calls reverse() on them
        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $messages,
        ]);
    }

    /**
     * Store a new message in a conversation.
     */
    public function storeMessage(Request $request, $id)
    {
        $user = $request->user();
        $conversation = $this->resolveConversation($id);

        // Security check: user must be a participant or admin
        $isParticipant = $conversation->participants()->where('user_id', $user->id)->exists();
        if (!$isParticipant && !$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'body' => 'nullable|string',
            'attachment' => 'nullable|file|max:5120|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx,zip',
        ]);

        if (empty($validated['body']) && !$request->hasFile('attachment')) {
            return response()->json(['error' => 'Message body or attachment is required.'], 422);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('message_attachments', 'public');
        }

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $validated['body'] ?? '',
            'attachment' => $attachmentPath,
        ]);

        $message->load('sender');

        // Broadcast real-time message event
        broadcast(new MessageSent($message))->toOthers();

        // Touch the conversation's timestamp
        $conversation->touch();

        return response()->json($message);
    }

    /**
     * Mark conversation as read for the authenticated user.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $conversation = $this->resolveConversation($id);

        $participant = ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->update([
                'last_read_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
