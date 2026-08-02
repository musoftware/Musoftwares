<?php

namespace App\Services\AI\Tools;

use App\Models\Project;
use App\Models\ProjectComment;

/**
 * Semantic conversation history search tool.
 *
 * This tool enables the AI to search through the FULL conversation history
 * of a project beyond the active 15-message context window. When the client
 * references something said earlier (e.g. "remember the budget I mentioned?"),
 * the AI should call this tool with a relevant keyword to retrieve matching
 * past messages instead of saying "I don't remember".
 */
class SearchConversationHistoryTool implements AiToolInterface
{
    public function name(): string
    {
        return 'search_conversation_history';
    }

    public function description(): string
    {
        return 'Search the full conversation history for messages containing a specific keyword or phrase. '
            . 'Use this when the client refers to something that may have been said earlier in the conversation '
            . 'and is outside the current context window (e.g. an old budget, feature, or decision). '
            . 'Returns the most relevant matching messages with their sender and content.';
    }

    public function parameters(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'query' => [
                    'type'        => 'string',
                    'description' => 'The keyword, phrase, or topic to search for in the conversation history (e.g. "budget", "payment", "login feature", "deadline").',
                ],
                'limit' => [
                    'type'        => 'integer',
                    'description' => 'Maximum number of matching messages to return. Defaults to 5.',
                    'minimum'     => 1,
                    'maximum'     => 10,
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $query = trim($arguments['query'] ?? '');
        $limit = min(10, max(1, (int) ($arguments['limit'] ?? 5)));

        if (empty($query)) {
            return [
                'success' => false,
                'action'  => 'search_conversation_history',
                'detail'  => 'No search query provided.',
                'results' => [],
            ];
        }

        // Full-text keyword search across all project messages
        $matches = ProjectComment::where('project_id', $project->id)
            ->where('body', 'LIKE', '%' . $query . '%')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        if ($matches->isEmpty()) {
            return [
                'success' => true,
                'action'  => 'search_conversation_history',
                'detail'  => "No messages found matching: \"{$query}\"",
                'results' => [],
            ];
        }

        $results = $matches->map(function ($comment) {
            $sender = $comment->author_id ? 'Client' : 'AI';
            $date   = $comment->created_at?->format('Y-m-d H:i') ?? '';
            $body   = mb_strimwidth(strip_tags($comment->body), 0, 300, '...');

            return [
                'sender'  => $sender,
                'date'    => $date,
                'message' => $body,
            ];
        })->values()->all();

        return [
            'success' => true,
            'action'  => 'search_conversation_history',
            'detail'  => "Found " . count($results) . " message(s) matching \"{$query}\"",
            'results' => $results,
        ];
    }
}
