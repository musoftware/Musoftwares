<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\MessageActivity;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Group message activities by thread_type and thread_id
            $activities = MessageActivity::with(['activity'])->orderBy('created_at', 'asc')->get();
            
            $conversationsMap = [];
            
            foreach ($activities as $activity) {
                $key = $activity->thread_type . '_' . $activity->thread_id;
                
                if (!isset($conversationsMap[$key])) {
                    $conversation = Conversation::where('conversable_type', $activity->thread_type)
                                                ->where('conversable_id', $activity->thread_id)
                                                ->first();
                    
                    if (!$conversation) {
                        $type = 'chat';
                        if ($activity->thread_type === 'App\Models\Ticket') {
                            $type = 'support_ticket';
                        } elseif ($activity->thread_type === 'App\Models\UserThread') {
                            $type = 'direct_message';
                        }

                        $conversation = Conversation::create([
                            'conversable_type' => $activity->thread_type,
                            'conversable_id'   => $activity->thread_id,
                            'type'             => $type,
                            'status'           => 'open',
                            'created_at'       => $activity->created_at,
                            'updated_at'       => $activity->created_at,
                        ]);
                    }
                    $conversationsMap[$key] = $conversation;
                }
                
                $conversation = $conversationsMap[$key];
                
                $body = '';
                $attachment = null;
                
                if ($activity->activity) {
                    if ($activity->activity_type === 'App\Models\MessageMessage') {
                        $body = $activity->activity->message;
                    } elseif ($activity->activity_type === 'App\Models\MessageImage') {
                        $body = 'Sent an image';
                        $attachment = $activity->activity->image_file;
                    } elseif ($activity->activity_type === 'App\Models\MessageFile') {
                        $body = 'Sent a file';
                        $attachment = $activity->activity->custom_file;
                    } elseif ($activity->activity_type === 'App\Models\MessageVoice') {
                        $body = 'Sent a voice message';
                        $attachment = $activity->activity->audio_file;
                    }
                }
                
                if (!$activity->activity && empty($body)) {
                    continue;
                }
                
                $conversation->messages()->create([
                    'sender_id'  => $activity->user_id,
                    'body'       => $body ?? '',
                    'attachment' => $attachment,
                    'is_system'  => false,
                    'created_at' => $activity->created_at,
                    'updated_at' => $activity->updated_at,
                ]);
                
                // Add the sender as participant if not already added
                $participantExists = $conversation->participants()
                                                  ->where('user_id', $activity->user_id)
                                                  ->exists();
                if (!$participantExists) {
                    $conversation->participants()->create([
                        'user_id' => $activity->user_id,
                        'role'    => 'participant'
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 
    }
};
