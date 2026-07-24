<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * Fetch messages for a conversation.
     */
    public function messages(Request $request, int|string $id): JsonResponse
    {
        $user = $request->user();
        $conversation = $this->resolveConversation($id);

        $this->authorizeAccess($conversation, $user);

        // Messages sorted descending (newest first) as ChatWindow expects
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
    public function storeMessage(Request $request, int|string $id): JsonResponse
    {
        $user = $request->user();
        $conversation = $this->resolveConversation($id);

        $this->authorizeAccess($conversation, $user);

        $validated = $request->validate([
            'body' => 'nullable|string',
            'attachment' => 'nullable|file|max:5120|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx,zip',
        ]);

        if (empty($validated['body']) && ! $request->hasFile('attachment')) {
            return response()->json(['error' => __('general.message_body_or_attachment_required') ?: 'Message body or attachment is required.'], 422);
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

        // Touch conversation updated_at timestamp
        $conversation->touch();

        return response()->json($message);
    }

    /**
     * Mark conversation as read for the authenticated user.
     */
    public function markAsRead(Request $request, int|string $id): JsonResponse
    {
        $user = $request->user();
        $conversation = $this->resolveConversation($id);

        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update([
                'last_read_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Helper to resolve conversation by ID or ticket ID.
     */
    private function resolveConversation(int|string $id): Conversation
    {
        $conversation = Conversation::find($id)
            ?? Conversation::where('conversable_type', Ticket::class)
                ->where('conversable_id', $id)
                ->first();

        if (! $conversation) {
            abort(404, __('general.conversation_not_found'));
        }

        return $conversation;
    }

    /**
     * Guard to verify user is a participant or administrator.
     */
    private function authorizeAccess(Conversation $conversation, User $user): void
    {
        $isParticipant = $conversation->participants()->where('user_id', $user->id)->exists();
        $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : $user->hasRole(['admin', 'super_admin']);

        if (! $isParticipant && ! $isAdmin) {
            abort(403, 'Unauthorized access to conversation.');
        }
    }
}

