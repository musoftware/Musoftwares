<?php

namespace App\Services\AI;

use App\Models\AdminSettings;
use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\ProjectBoardNote;
use App\Models\ProjectReport;
use App\Models\Task;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProjectBoardAiService
{
    /**
     * Generate 3-4 custom clarifying questions based on the user's initial goal/prompt.
     */
    public function generatePlanQuestions(Project $project, string $userPrompt): array
    {
        $defaultProvider = AdminSettings::GetValue('default_ai_model', 'openai');
        if ($defaultProvider === 'openai') {
            $apiKey = AdminSettings::GetValue('openai_api_key', config('services.openai.key'));
            $model = AdminSettings::GetValue('openai_model', 'gpt-4o-mini');
        } else {
            $apiKey = AdminSettings::GetValue('gemini_api_key', config('services.gemini.key'));
            $model = AdminSettings::GetValue('gemini_model', 'gemini-2.0-flash');
        }

        if (empty($apiKey)) {
            throw new \Exception("AI integration is not configured. Please set your {$defaultProvider} API key in admin settings.");
        }

        $systemPrompt = "You are an expert project manager. The user wants to generate a project board timeline for: '{$userPrompt}' in the project '{$project->project_name}'.\n"
            . "To create an extremely detailed, professional, and custom plan (with specific tasks, todos, and reports rather than generic placeholders), generate exactly 3 or 4 highly relevant, brief clarifying questions to ask the user.\n"
            . "Ask about specific technical stacks, integrations, workflows, key features, or constraints.\n"
            . "Auto-detect the language of the prompt (Arabic or English) and output questions in the SAME language.\n"
            . "Return ONLY a valid JSON object matching this schema. Do not include markdown wraps.\n\n"
            . "JSON Schema:\n"
            . "{\n"
            . "  \"questions\": [\n"
            . "     \"Question 1?\",\n"
            . "     \"Question 2?\",\n"
            . "     \"Question 3?\"\n"
            . "  ]\n"
            . "}";

        $content = $this->callAi($defaultProvider, $apiKey, $model, $systemPrompt, $userPrompt);
        $data = json_decode($content, true);

        if (!$data || !isset($data['questions'])) {
            Log::error('ProjectBoardAiService generatePlanQuestions: failed to parse JSON', ['content' => $content]);
            return [
                'questions' => [
                    'What are the main deliverables for this plan?',
                    'Are there specific tools or APIs to integrate?',
                    'Are there any key constraints or deadlines?'
                ]
            ];
        }

        return $data;
    }

    /**
     * Generate a structured timeline/plan for the project based on the prompt and optional Q&A answers.
     */
    public function generatePlan(
        Project $project,
        string $userPrompt,
        string $startDate,
        int $userId,
        array $allowedTypes = ['note', 'task', 'todo', 'report'],
        int $maxDailyHours = 8,
        array $skipDays = [5], // Carbon day-of-week integers; 5 = Friday
        array $answers = [] // User answers to clarify details
    ): array
    {
        $defaultProvider = AdminSettings::GetValue('default_ai_model', 'openai');
        if ($defaultProvider === 'openai') {
            $apiKey = AdminSettings::GetValue('openai_api_key', config('services.openai.key'));
            $model = AdminSettings::GetValue('openai_model', 'gpt-4o-mini');
        } else {
            $apiKey = AdminSettings::GetValue('gemini_api_key', config('services.gemini.key'));
            $model = AdminSettings::GetValue('gemini_model', 'gemini-2.0-flash');
        }

        if (empty($apiKey)) {
            throw new \Exception("AI integration is not configured. Please set your {$defaultProvider} API key in admin settings.");
        }

        // Build a human-readable list of allowed types for the AI prompt.
        $allowedTypesStr = implode(', ', array_map(fn($t) => "'{$t}'", $allowedTypes));
        $allowedTypeDescriptions = [];
        if (in_array('task', $allowedTypes)) {
            $allowedTypeDescriptions[] = "- 'task': A specific task to do. Must include 'estimated_hours' (decimal/float or integer, e.g. 2, 3.5).";
        }
        if (in_array('todo', $allowedTypes)) {
            $allowedTypeDescriptions[] = "- 'todo': A checklist card. Can include 'checklist' array of string items.";
        }
        if (in_array('report', $allowedTypes)) {
            $allowedTypeDescriptions[] = "- 'report': A progress report or milestone summary card.";
        }
        if (in_array('note', $allowedTypes)) {
            $allowedTypeDescriptions[] = "- 'note': A general documentation or informational card.";
        }
        $typeDescriptionsStr = implode("\n", $allowedTypeDescriptions);
        $allowedTypesJsonStr = implode('\" | \"', $allowedTypes);

        // Format the Q&As for the AI context to enforce specificity
        $qasStr = "";
        if (!empty($answers)) {
            $qasStr = "\nThe user has provided the following clarifications about their project:\n";
            foreach ($answers as $q => $a) {
                if (trim($a)) {
                    $qasStr .= "- Q: {$q}\n  A: {$a}\n";
                }
            }
        }

        $systemPrompt = "You are a professional project manager. The user wants to generate a project board timeline/plan for their project.\n"
            . "Project name: {$project->project_name}\n"
            . $qasStr . "\n"
            . "CRITICAL Instructions:\n"
            . "1. DO NOT WRITE GENERIC OR VAGUE TASKS/REPORTS. Avoid titles like 'Review performance', 'Identify missing features', 'Notes about analysis', 'Phase Scope: Analysing requirements', 'Add details'.\n"
            . "2. Every card title and description must be highly operational, detailed, specific, and actionable. Write exact names of components, databases, configurations, steps, or code setups relevant to the user's project and their answers.\n"
            . "3. Divide the project into logical sequential phases.\n"
            . "4. For each phase:\n"
            . "   - Provide a phase name.\n"
            . "   - Provide a phase scope (a high-level summary of requirements and scope for this phase).\n"
            . "   - Provide a list of board items. IMPORTANT: You may ONLY use the following card types: {$allowedTypesStr}.\n"
            . "     Do NOT generate any card type not in this list.\n"
            . "{$typeDescriptionsStr}\n"
            . "5. Each working day has a maximum of {$maxDailyHours} hours of tasks. Do not schedule more than {$maxDailyHours} total estimated_hours of tasks on a single day.\n"
            . "6. Mark exactly one key/critical task or milestone in the plan as 'is_important' = true.\n"
            . "7. Auto-detect the language of the user's prompt (usually Arabic or English) and output titles and descriptions in that SAME language.\n"
            . "8. Return ONLY a valid JSON object matching this schema. Do not include markdown wraps or anything else.\n\n"
            . "JSON Schema:\n"
            . "{\n"
            . "  \"phases\": [\n"
            . "    {\n"
            . "      \"name\": \"Phase Name\",\n"
            . "      \"scope\": \"Phase scope/goals summary\",\n"
            . "      \"items\": [\n"
            . "        {\n"
            . "          \"type\": \"{$allowedTypesJsonStr}\",\n"
            . "          \"title\": \"Item title\",\n"
            . "          \"description\": \"Item description (markdown format)\",\n"
            . "          \"estimated_hours\": 4, // only for task, float/int\n"
            . "          \"is_important\": false, // set true only for the key milestone of the project\n"
            . "          \"checklist\": [\"sub-task 1\", \"sub-task 2\"] // only for todo\n"
            . "        }\n"
            . "      ]\n"
            . "    }\n"
            . "  ]\n"
            . "}";


        $content = $this->callAi($defaultProvider, $apiKey, $model, $systemPrompt, $userPrompt);

        $data = json_decode($content, true);
        if (!$data || !isset($data['phases'])) {
            Log::error('ProjectBoardAiService: failed to parse JSON', ['content' => $content]);
            throw new \Exception('AI failed to generate a valid project plan. Please try again.');
        }

        return DB::transaction(function () use ($project, $data, $startDate, $userId, $allowedTypes, $maxDailyHours, $skipDays) {
            $currentDate = Carbon::parse($startDate);
            $newCards = [];

            // Helper: advance date past any skipped days
            $advancePastSkippedDays = function () use (&$currentDate, $skipDays) {
                $guard = 0;
                while (in_array($currentDate->dayOfWeek, $skipDays, true) && $guard < 14) {
                    $currentDate->addDay();
                    $guard++;
                }
            };

            $advancePastSkippedDays();

            foreach ($data['phases'] as $phase) {
                $advancePastSkippedDays();

                // 1. Create the Scope note at the start of the phase (only if 'note' is an allowed type)
                if (in_array('note', $allowedTypes, true)) {
                    $scopeNote = $project->boardNotes()->create([
                        'author_id' => $userId,
                        'for_date' => $currentDate->toDateString(),
                        'title' => "Phase Scope: {$phase['name']}",
                        'content' => $phase['scope'],
                        'color' => 'indigo',
                    ]);

                    $scopePlacement = $this->placeItem($project, $currentDate->toDateString(), ProjectBoardNote::class, $scopeNote->id, 'backlog', true, false);
                    $newCards[] = $this->serializeCard('note', $scopeNote, $scopePlacement);
                }

                $dailyHours = 0;

                // 2. Add each item
                foreach ($phase['items'] as $item) {
                    $type = $item['type'];

                    // Skip types the user did not allow
                    if (!in_array($type, $allowedTypes, true)) {
                        continue;
                    }

                    $title = $item['title'];
                    $desc = $item['description'] ?? '';
                    $isImportant = (bool)($item['is_important'] ?? false);
                    $estHours = isset($item['estimated_hours']) ? (float)$item['estimated_hours'] : 0;

                    // If it is a task, check if adding it exceeds the daily hour limit
                    if ($type === 'task') {
                        if ($dailyHours + $estHours > $maxDailyHours && $dailyHours > 0) {
                            $currentDate->addDay();
                            $advancePastSkippedDays();
                            $dailyHours = 0;
                        }
                    }

                    // Ensure we skip restricted days for any card type
                    $advancePastSkippedDays();

                    $createdItem = null;
                    $morphClass = null;

                    if ($type === 'note') {
                        $createdItem = $project->boardNotes()->create([
                            'author_id' => $userId,
                            'for_date' => $currentDate->toDateString(),
                            'title' => $title,
                            'content' => $desc,
                            'color' => 'yellow',
                        ]);
                        $morphClass = ProjectBoardNote::class;
                    } elseif ($type === 'task') {
                        $createdItem = $project->tasks()->create([
                            'user_id' => $project->user_id,
                            'task_name' => $title,
                            'task_description' => $desc,
                            'due_date' => $currentDate->toDateString(),
                            'priority' => $isImportant ? 'urgent' : 'normal',
                        ]);
                        $morphClass = Task::class;
                        $dailyHours += $estHours;
                    } elseif ($type === 'todo') {
                        $createdItem = $project->todos()->create([
                            'project_id' => $project->id,
                            'user_id' => $userId,
                            'title' => $title,
                            'description' => $desc,
                            'inDate' => $currentDate->toDateString(),
                            'completed' => false,
                            'priority' => 'normal',
                            'priorityColor' => 'gray',
                            'tags' => '[]',
                            'paused' => false,
                        ]);
                        $morphClass = Todo::class;

                        // Create checklist items if present
                        if (isset($item['checklist']) && is_array($item['checklist'])) {
                            foreach ($item['checklist'] as $chkTitle) {
                                $createdItem->checklistItems()->create([
                                    'title' => $chkTitle,
                                    'is_completed' => false,
                                ]);
                            }
                        }
                    } elseif ($type === 'report') {
                        $createdItem = $project->reports()->create([
                            'project_id' => $project->id,
                            'author_id' => $userId,
                            'title' => $title,
                            'body' => $desc,
                            'published_at' => $currentDate->toDateString() . 'T12:00:00',
                            'period_start' => $currentDate->toDateString() . 'T09:00:00',
                            'period_end' => $currentDate->toDateString() . 'T17:00:00',
                        ]);
                        $morphClass = ProjectReport::class;
                    }

                    if ($createdItem) {
                        $placement = $this->placeItem(
                            $project,
                            $currentDate->toDateString(),
                            $morphClass,
                            $createdItem->id,
                            'backlog',
                            true,
                            $isImportant
                        );
                        $newCards[] = $this->serializeCard($type, $createdItem, $placement);
                    }
                }

                // Increment date to start the next phase on a new day
                $currentDate->addDay();
            }

            return $newCards;
        });
    }

    /**
     * Adjust future timeline items based on comment feedback.
     */
    public function adjustFutureItems(Project $project, string $commentBody, int $commentedItemId, string $commentedItemType, int $userId): void
    {
        $defaultProvider = AdminSettings::GetValue('default_ai_model', 'openai');
        if ($defaultProvider === 'openai') {
            $apiKey = AdminSettings::GetValue('openai_api_key', config('services.openai.key'));
            $model = AdminSettings::GetValue('openai_model', 'gpt-4o-mini');
        } else {
            $apiKey = AdminSettings::GetValue('gemini_api_key', config('services.gemini.key'));
            $model = AdminSettings::GetValue('gemini_model', 'gemini-2.0-flash');
        }

        if (empty($apiKey)) {
            throw new \Exception("AI integration is not configured. Please set your {$defaultProvider} API key.");
        }

        $commentedBoardItem = ProjectBoardItem::where('project_id', $project->id)
            ->where('itemable_type', $commentedItemType)
            ->where('itemable_id', $commentedItemId)
            ->first();

        if (!$commentedBoardItem) {
            return;
        }

        $commentedItemable = $commentedBoardItem->itemable;
        $type = array_search($commentedItemType, ProjectBoardItem::MORPH_MAP) ?: 'note';
        $commentedTitle = $type === 'task' ? $commentedItemable->task_name : $commentedItemable->title;
        $commentedDesc = $type === 'task' ? $commentedItemable->task_description : ($type === 'note' ? $commentedItemable->content : $commentedItemable->description);

        // Fetch all future AI items starting on or after the commented item's date
        $futureBoardItems = ProjectBoardItem::where('project_id', $project->id)
            ->whereDate('for_date', '>=', $commentedBoardItem->for_date)
            ->where('is_ai', true)
            ->with('itemable')
            ->get();

        $timeline = [];
        foreach ($futureBoardItems as $bi) {
            $itemable = $bi->itemable;
            if (!$itemable) continue;

            $cType = array_search(get_class($itemable), ProjectBoardItem::MORPH_MAP);
            $cTitle = $cType === 'task' ? $itemable->task_name : $itemable->title;
            $cDesc = $cType === 'task' ? $itemable->task_description : ($cType === 'note' ? $itemable->content : $itemable->description);

            $timeline[] = [
                'id' => $bi->id,
                'type' => $cType,
                'title' => $cTitle,
                'description' => $cDesc,
                'for_date' => $bi->for_date,
                'lane' => $bi->lane,
                'is_important' => (bool)$bi->is_important,
            ];
        }

        $systemPrompt = "You are a professional project manager. The user has a planned timeline of AI-generated cards. They have left a comment on one of the cards.\n"
            . "Your job is to revise and adjust the future timeline (on or after the commented card's date) based on the user's comment/feedback.\n\n"
            . "Commented Card Details:\n"
            . "- Type: {$type}\n"
            . "- Title: {$commentedTitle}\n"
            . "- Description: {$commentedDesc}\n\n"
            . "User Comment: \"{$commentBody}\"\n\n"
            . "Current Future Timeline:\n"
            . json_encode($timeline, JSON_PRETTY_PRINT) . "\n\n"
            . "Instructions:\n"
            . "1. Adjust the future timeline to incorporate the user's comment (e.g. modify existing tasks/notes/todos, delete tasks/notes/todos, or add new ones).\n"
            . "2. For each task, provide an 'estimated_hours' field (decimal/float or integer).\n"
            . "3. Ensure at most one card in the entire timeline is marked as 'is_important' = true.\n"
            . "4. Auto-detect the language of the comment and write titles/descriptions in that SAME language.\n"
            . "5. Return ONLY a valid JSON object matching the schema below. Do not include markdown wraps.\n\n"
            . "JSON Schema:\n"
            . "{\n"
            . "  \"timeline\": [\n"
            . "    {\n"
            . "      \"action\": \"modify\" | \"delete\" | \"add\",\n"
            . "      \"id\": 123, // required for 'modify' or 'delete', corresponds to the database board item id from the current timeline\n"
            . "      \"type\": \"task\" | \"todo\" | \"report\" | \"note\", // required for 'add' or 'modify'\n"
            . "      \"title\": \"Updated title\", // required for 'add' or 'modify'\n"
            . "      \"description\": \"Updated description (markdown format)\", // required for 'add' or 'modify'\n"
            . "      \"estimated_hours\": 4, // only for task, float/int\n"
            . "      \"is_important\": false, // set true for the key milestone\n"
            . "      \"checklist\": [\"sub-task 1\", \"sub-task 2\"] // only for todo\n"
            . "    }\n"
            . "  ]\n"
            . "}";

        $content = $this->callAi($defaultProvider, $apiKey, $model, $systemPrompt, $commentBody);

        $data = json_decode($content, true);
        if (!$data || !isset($data['timeline'])) {
            Log::error('ProjectBoardAiService adjustFutureItems: failed to parse JSON', ['content' => $content]);
            throw new \Exception('AI failed to parse timeline adjustments. Please try again.');
        }

        DB::transaction(function () use ($project, $data, $futureBoardItems, $commentedBoardItem, $userId) {
            $currentDate = Carbon::parse($commentedBoardItem->for_date);
            $itemsToSchedule = [];

            // Group existing future items by ID for quick lookup
            $boardItemsById = $futureBoardItems->keyBy('id');

            foreach ($data['timeline'] as $actionItem) {
                $action = $actionItem['action'];
                $id = $actionItem['id'] ?? null;
                $type = $actionItem['type'] ?? 'note';
                $title = $actionItem['title'] ?? '';
                $desc = $actionItem['description'] ?? '';
                $isImportant = (bool)($actionItem['is_important'] ?? false);
                $estHours = isset($actionItem['estimated_hours']) ? (float)$actionItem['estimated_hours'] : 0;

                if ($action === 'delete') {
                    if ($id && isset($boardItemsById[$id])) {
                        $bi = $boardItemsById[$id];
                        $itemable = $bi->itemable;
                        $bi->delete();
                        if ($itemable) {
                            $itemable->delete();
                        }
                    }
                    continue;
                }

                if ($action === 'modify') {
                    if ($id && isset($boardItemsById[$id])) {
                        $bi = $boardItemsById[$id];
                        $itemable = $bi->itemable;

                        if ($itemable) {
                            if ($itemable instanceof Task) {
                                $itemable->update([
                                    'task_name' => $title,
                                    'task_description' => $desc,
                                    'priority' => $isImportant ? 'urgent' : 'normal',
                                ]);
                            } elseif ($itemable instanceof ProjectBoardNote) {
                                $itemable->update([
                                    'title' => $title,
                                    'content' => $desc,
                                ]);
                            } elseif ($itemable instanceof Todo) {
                                $itemable->update([
                                    'title' => $title,
                                    'description' => $desc,
                                ]);
                            } elseif ($itemable instanceof ProjectReport) {
                                $itemable->update([
                                    'title' => $title,
                                    'body' => $desc,
                                ]);
                            }
                        }

                        $itemsToSchedule[] = [
                            'board_item' => $bi,
                            'type' => $type,
                            'is_important' => $isImportant,
                            'estimated_hours' => $estHours,
                        ];

                        // Remove from active list so we know which ones were untouched
                        $boardItemsById->forget($id);
                    }
                    continue;
                }

                if ($action === 'add') {
                    $createdItem = null;
                    $morphClass = null;

                    if ($type === 'note') {
                        $createdItem = $project->boardNotes()->create([
                            'author_id' => $userId,
                            'for_date' => $currentDate->toDateString(),
                            'title' => $title,
                            'content' => $desc,
                            'color' => 'yellow',
                        ]);
                        $morphClass = ProjectBoardNote::class;
                    } elseif ($type === 'task') {
                        $createdItem = $project->tasks()->create([
                            'user_id' => $project->user_id,
                            'task_name' => $title,
                            'task_description' => $desc,
                            'due_date' => $currentDate->toDateString(),
                            'priority' => $isImportant ? 'urgent' : 'normal',
                        ]);
                        $morphClass = Task::class;
                    } elseif ($type === 'todo') {
                        $createdItem = $project->todos()->create([
                            'project_id' => $project->id,
                            'user_id' => $userId,
                            'title' => $title,
                            'description' => $desc,
                            'inDate' => $currentDate->toDateString(),
                            'completed' => false,
                            'priority' => 'normal',
                            'priorityColor' => 'gray',
                            'tags' => '[]',
                            'paused' => false,
                        ]);
                        $morphClass = Todo::class;

                        if (isset($actionItem['checklist']) && is_array($actionItem['checklist'])) {
                            foreach ($actionItem['checklist'] as $chkTitle) {
                                $createdItem->checklistItems()->create([
                                    'title' => $chkTitle,
                                    'is_completed' => false,
                                ]);
                            }
                        }
                    } elseif ($type === 'report') {
                        $createdItem = $project->reports()->create([
                            'project_id' => $project->id,
                            'author_id' => $userId,
                            'title' => $title,
                            'body' => $desc,
                            'published_at' => $currentDate->toDateString() . 'T12:00:00',
                            'period_start' => $currentDate->toDateString() . 'T09:00:00',
                            'period_end' => $currentDate->toDateString() . 'T17:00:00',
                        ]);
                        $morphClass = ProjectReport::class;
                    }

                    if ($createdItem) {
                        $newBi = ProjectBoardItem::create([
                            'project_id' => $project->id,
                            'for_date' => $currentDate->toDateString(),
                            'itemable_type' => $morphClass,
                            'itemable_id' => $createdItem->id,
                            'lane' => 'backlog',
                            'is_ai' => true,
                            'is_important' => $isImportant,
                        ]);

                        $itemsToSchedule[] = [
                            'board_item' => $newBi,
                            'type' => $type,
                            'is_important' => $isImportant,
                            'estimated_hours' => $estHours,
                        ];
                    }
                }
            }

            // Append any future board items that were untouched by the AI instructions
            foreach ($boardItemsById as $bi) {
                $itemable = $bi->itemable;
                if (!$itemable) continue;
                $cType = array_search(get_class($itemable), ProjectBoardItem::MORPH_MAP) ?: 'note';
                $itemsToSchedule[] = [
                    'board_item' => $bi,
                    'type' => $cType,
                    'is_important' => (bool)$bi->is_important,
                    'estimated_hours' => 2.0, // default placeholder
                ];
            }

            // Re-schedule everything sequentially starting from commented board item date
            $dailyHours = 0;
            foreach ($itemsToSchedule as $sched) {
                $bi = $sched['board_item'];
                $type = $sched['type'];
                $estHours = $sched['estimated_hours'];
                $isImportant = $sched['is_important'];

                if ($currentDate->dayOfWeek === Carbon::FRIDAY) {
                    $currentDate->addDay();
                    $dailyHours = 0;
                }

                if ($type === 'task') {
                    if ($dailyHours + $estHours > 8 && $dailyHours > 0) {
                        $currentDate->addDay();
                        if ($currentDate->dayOfWeek === Carbon::FRIDAY) {
                            $currentDate->addDay();
                        }
                        $dailyHours = 0;
                    }
                    $dailyHours += $estHours;
                }

                if ($currentDate->dayOfWeek === Carbon::FRIDAY) {
                    $currentDate->addDay();
                    $dailyHours = 0;
                }

                // Update board item database record
                $bi->for_date = $currentDate->toDateString();
                $bi->is_important = $isImportant;
                $bi->save();

                // Update underlying model due date / date if applicable
                $itemable = $bi->itemable;
                if ($itemable) {
                    if ($itemable instanceof Task) {
                        $itemable->due_date = $currentDate->toDateString();
                        $itemable->save();
                    } elseif ($itemable instanceof ProjectBoardNote) {
                        $itemable->for_date = $currentDate->toDateString();
                        $itemable->save();
                    } elseif ($itemable instanceof Todo) {
                        $itemable->inDate = $currentDate->toDateString();
                        $itemable->save();
                    } elseif ($itemable instanceof ProjectReport) {
                        $itemable->published_at = $currentDate->toDateString() . 'T12:00:00';
                        $itemable->period_start = $currentDate->toDateString() . 'T09:00:00';
                        $itemable->period_end = $currentDate->toDateString() . 'T17:00:00';
                        $itemable->save();
                    }
                }
            }
        });
    }

    /**
     * Interacts with OpenAI/Gemini to fetch JSON content.
     */
    private function callAi(string $provider, string $apiKey, string $model, string $systemPrompt, string $userPrompt): string
    {
        try {
            if ($provider === 'openai') {
                $response = Http::timeout(60)->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'temperature' => 0.5,
                ]);
                $content = $response->json()['choices'][0]['message']['content'] ?? '';
            } else {
                $response = Http::timeout(60)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $systemPrompt . "\n\nUser Input:\n" . $userPrompt]]],
                    ],
                ]);
                $content = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
            }

            $clean = preg_replace('/^```json\s*/i', '', $content);
            $clean = preg_replace('/^```\s*/i', '', $clean);
            $clean = preg_replace('/```$/', '', $clean);
            return trim($clean);

        } catch (\Exception $e) {
            Log::error('ProjectBoardAiService callAi error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Place item on board items mapping table.
     */
    private function placeItem(Project $project, string $date, string $morphClass, int $morphId, string $lane, bool $isAi, bool $isImportant): ProjectBoardItem
    {
        $attributes = [
            'project_id' => $project->id,
            'for_date' => $date,
            'itemable_type' => $morphClass,
            'itemable_id' => $morphId,
        ];

        $nextSort = (int) (ProjectBoardItem::where('project_id', $project->id)
            ->where('for_date', $date)
            ->where('lane', $lane)
            ->max('sort') ?? -1) + 1;

        return ProjectBoardItem::updateOrCreate($attributes, [
            'lane' => $lane,
            'pos_x' => 24,
            'pos_y' => 24,
            'sort' => $nextSort,
            'is_ai' => $isAi,
            'is_important' => $isImportant,
        ]);
    }

    /**
     * Serialize a generated card to matching frontend BoardCard structure.
     */
    private function serializeCard(string $type, $item, ProjectBoardItem $placement): array
    {
        $extra = [
            'comments_count' => 0,
            'sort' => (int) $placement->sort,
            'category_id' => null,
            'category' => null,
            'is_ai' => (bool)$placement->is_ai,
            'is_important' => (bool)$placement->is_important,
        ];

        if ($type === 'note') {
            $extra['color'] = $item->color;
            $extra['content'] = $item->content;
        } elseif ($type === 'task') {
            $extra['description'] = $item->task_description;
            $extra['priority'] = $item->priority;
            $extra['done'] = false;
        } elseif ($type === 'todo') {
            $extra['description'] = $item->description;
            $extra['completed'] = (bool)$item->completed;
            $extra['checklist'] = $item->checklistItems ? $item->checklistItems->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'is_completed' => (bool)$c->is_completed,
            ])->toArray() : [];
        } elseif ($type === 'report') {
            $extra['description'] = $item->body;
            $extra['body'] = $item->body;
            $extra['published_at'] = $item->published_at ? Carbon::parse($item->published_at)->toIso8601String() : null;
            $extra['period_start'] = $item->period_start ? Carbon::parse($item->period_start)->toIso8601String() : null;
            $extra['period_end'] = $item->period_end ? Carbon::parse($item->period_end)->toIso8601String() : null;
        }

        return array_merge([
            'type' => $type,
            'id' => $item->id,
            'title' => $type === 'task' ? $item->task_name : ($item->title ?: $item->original_name ?: ''),
            'lane' => $placement->lane,
            'pos_x' => (int)$placement->pos_x,
            'pos_y' => (int)$placement->pos_y,
        ], $extra);
    }
}
