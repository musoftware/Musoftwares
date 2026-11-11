<?php

namespace Modules\AppBuilder\Services\Chat;

use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * Who talks for free: a guest who has not seen a price yet, in the first messages. A signed-in customer, a chat with a
 * price, or a long chat goes to Claude. Off while no Gemini key is set.
 */
class FreeChat
{
    public function __construct(private readonly GeminiChatModel $gemini) {}

    public function applies(BuilderProject $project): bool
    {
        return filled(config('builder.free_chat.key'))
            && $project->user_id === null
            && (int) $project->quote_version === 0
            && $this->customerMessages($project) <= (int) config('builder.free_chat.turns');
    }

    public function model(): ChatModel
    {
        return $this->gemini;
    }

    /** A guest who keeps talking without a price is asked to sign in; a signed-in customer is never stopped here. */
    public function mustSignIn(BuilderProject $project): bool
    {
        return $project->user_id === null
            && (int) $project->quote_version === 0
            && $this->customerMessages($project) >= (int) config('builder.limits.guest_messages_before_sign_in');
    }

    private function customerMessages(BuilderProject $project): int
    {
        return $project->messages()->where('role', BuilderMessage::USER)->count();
    }
}
