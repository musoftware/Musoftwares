<?php

namespace Modules\AppBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AppBuilder\Models\BuilderEngineerQuestion;
use Modules\AppBuilder\Services\Chat\ProjectChat;
use Modules\AppBuilder\Services\EngineerQuestions;
use Modules\AppBuilder\Services\WorkerRoles;

/** The worker takes what the chat did not know, answers it, and the chat tells the customer. */
class EngineerQuestionController extends Controller
{
    public function __construct(private readonly EngineerQuestions $questions, private readonly ProjectChat $chat) {}

    public function pending(Request $request, WorkerRoles $roles): JsonResponse
    {
        // a backup worker waits while the main one is alive, so a question is answered once
        return response()->json(['questions' => $roles->mayTakeNewWork($request) ? $this->questions->forWorker() : []]);
    }

    public function store(Request $request, BuilderEngineerQuestion $question): JsonResponse
    {
        $data = $request->validate(['answer' => ['required', 'string', 'max:20000']]);
        if ($this->questions->answer($question, $data['answer'])) {
            $this->chat->continueAfterEngineer($question->refresh());
        }

        return response()->json(['status' => 'ok']);
    }
}
