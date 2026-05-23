<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class MessagesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Fetch all conversations where user is participant
        $conversations = Conversation::with(['participants.user', 'messages' => function($q) {
                $q->latest()->take(1);
            }])
            ->whereHas('participants', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest()
            ->get()
            ->map(function ($conv) use ($user) {
                $participant = $conv->participants->where('user_id', $user->id)->first();
                $lastRead = $participant->last_read_at ?? '2000-01-01 00:00:00';
                $conv->unread_count = $conv->messages()
                    ->where('sender_id', '!=', $user->id)
                    ->where('created_at', '>', $lastRead)
                    ->count();
                return $conv;
            });

        // Security: only expose admin/support accounts for direct chat.
        // Regular users must NOT see the full member directory.
        $users = User::where('id', '!=', $user->id)
            ->whereIn('role', ['admin', 'support', 'super_admin'])
            ->select('id', 'name', 'email', 'avatar', 'role')
            ->orderBy('name')
            ->get();

        return Inertia::render('Messages/Index', [
            'conversations' => $conversations,
            'users' => $users,
        ]);
    }

    public function storeDirectMessage(Request $request)
    {
        $request->validate([
            'recipient_id' => [
                'required',
                'exists:users,id',
                // Server-side guard: users can only direct-message admins/support
                function ($attribute, $value, $fail) use ($request) {
                    $recipient = User::find($value);
                    if ($recipient && !in_array($recipient->role, ['admin', 'support', 'super_admin'])) {
                        $fail('Direct messages can only be sent to support or admin accounts.');
                    }
                },
            ],
            'message' => 'required|string',
        ]);

        $sender = $request->user();
        $recipient = User::findOrFail($request->recipient_id);

        if ($sender->id === $recipient->id) {
            return back()->withErrors(['recipient_id' => 'Cannot send message to yourself.']);
        }

        try {
            $conversation = DB::transaction(function () use ($sender, $recipient, $request) {
                // Check if direct message conversation already exists between these two
                $existing = Conversation::where('type', 'direct_message')
                    ->whereHas('participants', function ($q) use ($sender) {
                        $q->where('user_id', $sender->id);
                    })
                    ->whereHas('participants', function ($q) use ($recipient) {
                        $q->where('user_id', $recipient->id);
                    })
                    ->first();

                if ($existing) {
                    $existing->messages()->create([
                        'sender_id' => $sender->id,
                        'body' => $request->message,
                    ]);
                    return $existing;
                }

                // Create new conversation
                $conv = Conversation::create([
                    'conversable_type' => User::class,
                    'conversable_id' => $sender->id,
                    'type' => 'direct_message',
                    'status' => 'open',
                ]);

                $conv->participants()->create([
                    'user_id' => $sender->id,
                    'role' => 'buyer',
                ]);

                $conv->participants()->create([
                    'user_id' => $recipient->id,
                    'role' => 'seller',
                ]);

                $conv->messages()->create([
                    'sender_id' => $sender->id,
                    'body' => $request->message,
                ]);

                return $conv;
            });

            return back()->with('success', 'Message sent successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Failed to send message: ' . $e->getMessage()]);
        }
    }
}

