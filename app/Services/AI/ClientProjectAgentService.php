<?php

namespace App\Services\AI;

use App\Models\AdminSettings;
use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\ProjectComment;
use App\Models\Task;
use App\Models\Todo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClientProjectAgentService
{
    /**
     * Process a client message on an AI-enabled project.
     */
    public function processMessage(Project $project, ProjectComment $comment): void
    {
        $defaultProvider = 'openai';
        $apiKey = AdminSettings::GetValue('openai_api_key', config('services.openai.key'));
        $model = AdminSettings::GetValue('openai_model', 'gpt-4o-mini');

        if (empty($apiKey)) {
            Log::error('OpenAI API key not configured in ClientProjectAgentService.');
            return;
        }

        // Fetch current project tasks and todos to give the AI context of existing items
        $existingTasks = Task::where('project_id', $project->id)->get(['id', 'task_name', 'priority', 'due_date']);
        $existingTodos = Todo::where('project_id', $project->id)->get(['id', 'title', 'completed']);

        $contextText = "Project Name: {$project->project_name}\n";
        $contextText .= "Current Developer Tasks:\n";
        foreach ($existingTasks as $t) {
            $contextText .= "- ID: {$t->id}, Name: {$t->task_name}, Priority: {$t->priority}, Due: {$t->due_date}\n";
        }
        $contextText .= "\nCurrent Developer Todos:\n";
        foreach ($existingTodos as $td) {
            $contextText .= "- ID: {$td->id}, Title: {$td->title}, Completed: " . ($td->completed ? 'Yes' : 'No') . "\n";
        }

        $systemPrompt = "You are a professional background Project Manager for software developers. The client has sent a message.\n"
            . "Your job is to translate the client's request into actionable developer tasks (or edit/delete/complete existing tasks and todos).\n"
            . "Do not worry about reporting finished work. Focus exclusively on building the list of tasks/todos the developer (programmer) needs to work on.\n\n"
            . "Current project state:\n{$contextText}\n\n"
            . "You can take multiple actions in response. Choose from these tool definitions:\n"
            . "1. create_task: Add a main developer feature/milestone. Arguments: {\"task_name\": string, \"task_description\": string, \"priority\": \"low\"|\"normal\"|\"high\"|\"urgent\"}\n"
            . "2. create_todo: Add a smaller developer todo item. Arguments: {\"title\": string, \"description\": string}\n"
            . "3. update_priority: Change task priority. Arguments: {\"task_id\": integer, \"priority\": \"low\"|\"normal\"|\"high\"|\"urgent\"}\n"
            . "4. delete_item: Remove a task/todo. Arguments: {\"item_type\": \"task\"|\"todo\", \"item_id\": integer}\n"
            . "5. mark_complete: Mark a task/todo as finished. Arguments: {\"item_type\": \"task\"|\"todo\", \"item_id\": integer}\n\n"
            . "Respond ONLY with a valid JSON object matching the schema below. Do not output markdown code blocks or extra text.\n\n"
            . "JSON Schema:\n"
            . "{\n"
            . "  \"actions\": [\n"
            . "     { \"tool\": \"create_task\", \"arguments\": { ... } }\n"
            . "  ],\n"
            . "  \"system_log\": \"A short description of what you did to display as a system card (e.g. 'Task Created - API Integration' or 'Task Priority Updated')\"\n"
            . "}";

        try {
            $content = $this->callAi($defaultProvider, $apiKey, $model, $systemPrompt, $comment->body);
            $data = json_decode($content, true);

            if (!$data || !isset($data['actions'])) {
                Log::error('ClientProjectAgentService: Failed to parse AI JSON response: ' . $content);
                return;
            }

            foreach ($data['actions'] as $action) {
                $tool = $action['tool'] ?? '';
                $args = $action['arguments'] ?? [];

                switch ($tool) {
                    case 'create_task':
                        $task = $project->tasks()->create([
                            'user_id' => $project->user_id,
                            'task_name' => $args['task_name'] ?? 'New AI Task',
                            'task_description' => $args['task_description'] ?? '',
                            'priority' => $args['priority'] ?? 'normal',
                            'due_date' => date('Y-m-d'),
                        ]);
                        // Place on Kanban Board
                        $this->placeItemOnBoard($project, Task::class, $task->id);
                        break;

                    case 'create_todo':
                        $todo = $project->todos()->create([
                            'project_id' => $project->id,
                            'user_id' => $project->user_id,
                            'title' => $args['title'] ?? 'New AI Todo',
                            'description' => $args['description'] ?? '',
                            'inDate' => date('Y-m-d'),
                            'completed' => false,
                            'priority' => 'normal',
                            'priorityColor' => 'gray',
                            'tags' => '[]',
                        ]);
                        // Place on Kanban Board
                        $this->placeItemOnBoard($project, Todo::class, $todo->id);
                        break;

                    case 'update_priority':
                        if (isset($args['task_id'])) {
                            Task::where('id', $args['task_id'])->update(['priority' => $args['priority'] ?? 'normal']);
                        }
                        break;

                    case 'delete_item':
                        $id = $args['item_id'] ?? null;
                        if ($id) {
                            if (($args['item_type'] ?? '') === 'task') {
                                Task::where('id', $id)->delete();
                            } else {
                                Todo::where('id', $id)->delete();
                            }
                        }
                        break;

                    case 'mark_complete':
                        $id = $args['item_id'] ?? null;
                        if ($id) {
                            if (($args['item_type'] ?? '') === 'task') {
                                Task::where('id', $id)->update(['archived' => true]);
                            } else {
                                Todo::where('id', $id)->update(['completed' => true]);
                            }
                        }
                        break;
                }
            }

            if (!empty($data['system_log'])) {
                $project->comments()->create([
                    'project_id' => $project->id,
                    'author_id' => null,
                    'guest_name' => 'System',
                    'body' => "[System: {$data['system_log']}]",
                    'commentable_type' => Project::class,
                    'commentable_id' => $project->id,
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('ClientProjectAgentService execution failed: ' . $e->getMessage());
        }
    }

    private function placeItemOnBoard(Project $project, string $morphClass, int $morphId)
    {
        $date = date('Y-m-d');
        $lane = 'backlog';

        $nextSort = (int) (ProjectBoardItem::where('project_id', $project->id)
            ->where('for_date', $date)
            ->where('lane', $lane)
            ->max('sort') ?? -1) + 1;

        ProjectBoardItem::create([
            'project_id' => $project->id,
            'for_date' => $date,
            'itemable_type' => $morphClass,
            'itemable_id' => $morphId,
            'lane' => $lane,
            'pos_x' => 24,
            'pos_y' => 24,
            'sort' => $nextSort,
            'is_ai' => true,
        ]);
    }

    private function callAi(string $provider, string $apiKey, string $model, string $systemPrompt, string $userPrompt): string
    {
        if ($provider === 'openai') {
            $response = Http::withoutVerifying()->withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.1,
            ]);

            if ($response->failed()) {
                throw new \Exception('OpenAI API call failed: ' . $response->body());
            }

            return $response->json('choices.0.message.content') ?? '';
        } else {
            // Gemini API call
            $response = Http::withoutVerifying()->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPrompt . "\n\nUser request: " . $userPrompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.1,
                ],
            ]);

            if ($response->failed()) {
                throw new \Exception('Gemini API call failed: ' . $response->body());
            }

            return $response->json('candidates.0.content.parts.0.text') ?? '';
        }
    }
}
