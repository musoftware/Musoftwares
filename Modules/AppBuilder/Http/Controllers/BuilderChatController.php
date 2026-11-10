<?php

namespace Modules\AppBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\AppBuilder\Http\Requests\SendMessageRequest;
use Modules\AppBuilder\Http\Resources\ProjectStateResource;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\Chat\ChatLimitReached;
use Modules\AppBuilder\Services\Chat\ChatUnavailable;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Services\AttachmentStore;
use Modules\AppBuilder\Services\BuilderReferral;
use Modules\AppBuilder\Services\BuildQuestions;
use Modules\AppBuilder\Services\Chat\ProjectChat;

class BuilderChatController extends Controller
{
    public function __construct(
        private readonly ProjectChat $chat,
        private readonly BuildQuestions $questions,
        private readonly AttachmentStore $attachments,
        private readonly BuilderReferral $referral,
    ) {}

    public function index(): View
    {
        return view('appbuilder::chat', ['state' => null, 'push' => \Modules\AppBuilder\Services\BuilderPush::webConfig()]);
    }

    public function show(BuilderProject $project): View
    {
        return view('appbuilder::chat', ['state' => $this->stateOf($project), 'push' => \Modules\AppBuilder\Services\BuilderPush::webConfig()]);
    }

    public function state(BuilderProject $project): JsonResponse
    {
        return response()->json($this->stateOf($project));
    }

    /** The first message opens the chat, so visitors who only look never create a project. */
    public function start(SendMessageRequest $request): JsonResponse
    {
        try {
            $project = $this->chat->start((string) $request->ip(), app()->getLocale(), $this->referral->referrerFor($request));
        } catch (ChatLimitReached) {
            return $this->refuse('builder.errors.limit', 429);
        }

        return $this->send($request, $project);
    }

    public function send(SendMessageRequest $request, BuilderProject $project): JsonResponse
    {
        if (! $project->isOpenForChat()) {
            return $this->refuse('builder.errors.busy', 409);
        }
        if ($project->isWaitingForAnswer()) {
            return $this->answerTheBuild($request, $project);
        }
        if ($project->hasBalanceDue() && $project->hasDelivery()) {
            return $this->refuse('builder.errors.balance_due', 409);
        }

        try {
            $this->chat->send($project, $request->text(), $request->uploads());
        } catch (ChatLimitReached) {
            return $this->refuse('builder.errors.limit', 429);
        } catch (ChatUnavailable) {
            return $this->refuse('builder.errors.unavailable', 503);
        }

        return response()->json($this->stateOf($project->refresh()));
    }

    /** The feasibility study as a printable page; the browser saves it as a PDF with Arabic shaped correctly. */
    public function study(BuilderProject $project): View
    {
        abort_unless($project->study_status === \Modules\AppBuilder\Services\Study\FeasibilityStudy::READY, 404);

        return view('appbuilder::study', ['project' => $project]);
    }

    /** The customer allowed notifications: their browser's token is kept with the project. */
    public function push(\Illuminate\Http\Request $request, BuilderProject $project, \Modules\AppBuilder\Services\BuilderPush $push): JsonResponse
    {
        $push->register($project, (string) $request->validate(['token' => ['required', 'string', 'max:500']])['token']);

        return response()->json(['ok' => true]);
    }

    /** The build asked something; this line goes to the engineer, not to the chat. */
    private function answerTheBuild(SendMessageRequest $request, BuilderProject $project): JsonResponse
    {
        $answer = $this->questions->answer($project, $request->text(), BuilderMessage::USER);
        $this->attachments->attach($answer, $request->uploads());

        return response()->json($this->stateOf($project->refresh()));
    }

    private function stateOf(BuilderProject $project): array
    {
        return (new ProjectStateResource($project->load('messages.attachments')))->resolve();
    }

    private function refuse(string $key, int $status): JsonResponse
    {
        return response()->json(['message' => __($key)], $status);
    }
}
