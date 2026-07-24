<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ServiceOrder;

class OrderMessageController extends Controller
{
    public function store(Request $request, ServiceOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $this->authorize('message', $order);

        $conversation = Conversation::where('conversable_type', ServiceOrder::class)
            ->where('conversable_id', $order->id)
            ->firstOrFail();

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        $message->load('sender');

        // Broadcast real-time message event
        broadcast(new MessageSent($message))->toOthers();

        // Touch conversation updated_at timestamp
        $conversation->touch();

        return redirect()->back()->with('success', __('general.message_sent'));
    }
}


