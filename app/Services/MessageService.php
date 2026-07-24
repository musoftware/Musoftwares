<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;

class MessageService extends BaseService
{
    public function sendDirectMessage(User $sender, User $recipient, string $messageBody): Conversation
    {
        return $this->executeInTransaction(function () use ($sender, $recipient, $messageBody) {
            $existing = Conversation::where('type', 'direct_message')
                ->whereHas('participants', function ($q) use ($sender) {
                    $q->where('user_id', $sender->id);
                })
                ->whereHas('participants', function ($q) use ($recipient) {
                    $q->where('user_id', $recipient->id);
                })
                ->first();

            if ($existing) {
                $msg = $existing->messages()->create([
                    'sender_id' => $sender->id,
                    'body' => $messageBody,
                ]);

                $msg->load(['sender', 'conversation']);
                $recipient->notify(new \App\Notifications\NewMessageNotification($msg));

                return $existing;
            }

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

            $msg = $conv->messages()->create([
                'sender_id' => $sender->id,
                'body' => $messageBody,
            ]);

            $msg->load(['sender', 'conversation']);
            $recipient->notify(new \App\Notifications\NewMessageNotification($msg));

            return $conv;
        });
    }
}
