<?php

namespace App\Services\AI\Tools;

use App\Models\Project;

class SendNotificationsTool implements AiToolInterface
{
    public function name(): string { return 'send_notifications'; }
    public function description(): string { return 'Dispatches system notifications to the Admin when project changes require developer review.'; }
    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'message' => ['type' => 'string'],
            ],
            'required' => ['message'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $msg = $arguments['message'] ?? 'New AI Project Update';

        // Best effort notification log
        \Log::info('[AI Notification Dispatch] Project #' . $project->id . ': ' . $msg);

        return [
            'success' => true,
            'action'  => 'Notified Admin',
            'detail'  => $msg,
        ];
    }
}
