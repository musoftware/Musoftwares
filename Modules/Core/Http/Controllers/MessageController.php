<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\Message;
use Modules\Core\Models\MessageAttachment;
use Illuminate\Support\Facades\Storage;
use App\Events\MessageSent;
use Modules\Core\Models\ConversationParticipant;

class MessageController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $conversation_id)
    {
        $isParticipant = ConversationParticipant::where('conversation_id', $conversation_id)
            ->where('user_id', auth()->id())
            ->exists();

        if (!$isParticipant) {
            abort(403, 'Unauthorized access to conversation.');
        }

        $request->validate([
            'body' => 'required_without:attachment|string|nullable',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
        ]);

        $message = Message::create([
            'conversation_id' => $conversation_id,
            'sender_id' => auth()->id(),
            'body' => $request->body,
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('attachments', 'public');

            MessageAttachment::create([
                'message_id' => $message->id,
                'type' => 'image',
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        // Load relations for broadcasting
        $message->load('attachments', 'sender');

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }
}
