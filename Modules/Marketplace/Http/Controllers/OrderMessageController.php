<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ServiceOrder;
use App\Models\Message;
use App\Models\Conversation;

class OrderMessageController extends Controller
{
    public function store(Request $request, ServiceOrder $order)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1000'
        ]);

        if (auth()->id() !== $order->buyer_id && auth()->id() !== $order->seller_id) {
            abort(403);
        }

        $conversation = Conversation::where('conversable_type', ServiceOrder::class)
            ->where('conversable_id', $order->id)
            ->firstOrFail();

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'body' => $validated['body']
        ]);

        return redirect()->back()->with('success', 'Message sent.');
    }
}

