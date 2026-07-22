<?php

namespace Modules\Marketplace\Services;

use App\Models\MessageActivity;
use App\Models\MessageActivityRead;
use App\Models\MessageMessage;
use App\Models\MessageVoice;
use App\Models\MessageFile;
use App\Models\MessageImage;
use App\Models\User;
use Modules\Marketplace\Models\ServiceOrder;
use Illuminate\Support\Facades\DB;

class OrderCollaborationService
{
    /**
     * Post text, voice, file or image message in order workspace.
     */
    public function sendMessage(ServiceOrder $order, User $sender, string $type, string $content, ?string $filePath = null): MessageActivity
    {
        return DB::transaction(function () use ($order, $sender, $type, $content, $filePath) {
            $activity = MessageActivity::create([
                'order_id' => $order->id,
                'user_id' => $sender->id,
                'thread_type' => ServiceOrder::class,
                'thread_id' => $order->id,
                'activity_type' => $type,
                'activity_id' => $order->id,
                'created_at' => now('Africa/Cairo'),
            ]);


            if ($type === 'text') {
                MessageMessage::create([
                    'thread_type' => ServiceOrder::class,
                    'thread_id' => $order->id,
                    'user_id' => $sender->id,
                    'message' => $content,
                ]);
            }

            return $activity;
        });
    }

    /**
     * Mark workspace activities as read by user.
     */
    public function markAsRead(int $activityId, User $user): MessageActivityRead
    {
        return MessageActivityRead::firstOrCreate([
            'message_activity_id' => $activityId,
            'user_id' => $user->id,
            'thread_type' => ServiceOrder::class,
            'thread_id' => $activityId,
        ], [
            'read' => 1,
        ]);
    }

}
