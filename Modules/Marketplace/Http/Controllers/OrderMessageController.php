<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Notifications\NewMessageNotification;
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

        $message->load(['sender', 'conversation']);

        // Notify recipient via FCM & Database notification
        $sender = $request->user();
        $recipients = collect();

        $conversation->load('participants.user');
        foreach ($conversation->participants as $participant) {
            if ($participant->user_id && (int) $participant->user_id !== (int) $sender->id) {
                $recipients->push($participant->user);
            }
        }

        if ($order->buyer_id && (int) $order->buyer_id !== (int) $sender->id && $order->buyer) {
            $recipients->push($order->buyer);
        }
        if ($order->seller_id && (int) $order->seller_id !== (int) $sender->id && $order->seller) {
            $recipients->push($order->seller);
        }

        $recipients->filter()->unique('id')->each(function ($recipient) use ($message) {
            $recipient->notify(new NewMessageNotification($message));
        });

        // Touch conversation updated_at timestamp
        $conversation->touch();

        return redirect()->back()->with('success', __('general.message_sent'));
    }
}



