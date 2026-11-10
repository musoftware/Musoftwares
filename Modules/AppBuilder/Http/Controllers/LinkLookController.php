<?php

namespace Modules\AppBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Services\Chat\ProjectChat;
use Modules\AppBuilder\Services\LinkLooks;
use Modules\AppBuilder\Services\WorkerRoles;

/** The worker asks for links customers shared, opens them in a browser in Docker, and sends back what it saw. */
class LinkLookController extends Controller
{
    public function __construct(private readonly LinkLooks $looks, private readonly ProjectChat $chat) {}

    public function pending(Request $request, WorkerRoles $roles): JsonResponse
    {
        // a backup worker waits while the main one is alive, so a link is opened once
        return response()->json(['looks' => $roles->mayTakeNewWork($request) ? $this->looks->forWorker() : []]);
    }

    public function store(Request $request, BuilderMessage $message): JsonResponse
    {
        $data = $request->validate([
            'index' => ['required', 'integer', 'min:0', 'max:10'],
            'reason' => ['nullable', 'string', 'max:300'],
            'title' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:1000'],
            'text' => ['nullable', 'string', 'max:20000'],
            'full_page' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:8192'],
            'pieces' => ['nullable', 'array', 'max:4'],
            'pieces.*' => ['file', 'mimes:jpg,jpeg,png', 'max:4096'],
        ]);

        $done = $this->looks->complete($message, (int) $data['index'], $data, $request->file('full_page'), $request->file('pieces', []));
        if ($done) {
            $this->chat->continueAfterLook($message->project);
        }

        return response()->json(['status' => 'ok']);
    }
}
