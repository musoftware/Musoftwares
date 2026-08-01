<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;

class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $tokens;
    public array $messageData;

    /**
     * Create a new job instance.
     *
     * @param array $tokens List of destination device tokens (empty for topic messages)
     * @param array $messageData Array representation of the CloudMessage
     */
    public function __construct(array $tokens, array $messageData)
    {
        $this->tokens = $tokens;
        $this->messageData = $messageData;
    }

    /**
     * Execute the job.
     */
    public function handle(Messaging $messaging): void
    {
        $message = CloudMessage::fromArray($this->messageData);

        if (! empty($this->tokens)) {
            $validTokens = array_values(array_filter($this->tokens, fn ($t) => is_string($t) && $t !== ''));
            if (empty($validTokens)) {
                return;
            }

            if (count($validTokens) === 1) {
                $messaging->send($message->withTarget('token', $validTokens[0]));
            } else {
                // Kreait Firebase multicast chunks at most 500 tokens per request
                foreach (array_chunk($validTokens, 500) as $chunk) {
                    $messaging->sendMulticast($message, $chunk);
                }
            }
        } else {
            // Send directly (e.g. topic, condition target embedded in messageData)
            $messaging->send($message);
        }
    }
}
