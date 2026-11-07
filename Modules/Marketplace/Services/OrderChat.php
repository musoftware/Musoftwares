<?php

namespace Modules\Marketplace\Services;

use App\Models\Conversation;
use App\Models\Message;
use Modules\Marketplace\Models\ServiceOrder;

/** Posts a message into an order's conversation, creating the conversation the first time. */
class OrderChat
{
    public function post(ServiceOrder $order, int $senderId, string $text): Message
    {
        $conversation = Conversation::firstOrCreate(
            ['conversable_type' => ServiceOrder::class, 'conversable_id' => $order->id],
            ['type' => 'marketplace_order', 'status' => 'open']
        );

        if (! $conversation->participants()->exists()) {
            $conversation->participants()->createMany([
                ['user_id' => $order->buyer_id, 'role' => 'buyer'],
                ['user_id' => $order->seller_id, 'role' => 'seller'],
            ]);
        }

        return Message::create(['conversation_id' => $conversation->id, 'sender_id' => $senderId, 'body' => $text]);
    }
}
