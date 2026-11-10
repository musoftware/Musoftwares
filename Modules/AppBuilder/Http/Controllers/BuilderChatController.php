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

    /** The start page: big or simple project, and the signed-in customer's own projects. */
    public function index(\Illuminate\Http\Request $request): View
    {
        return view('appbuilder::chat', [
            'state' => null,
            'push' => \Modules\AppBuilder\Services\BuilderPush::webConfig(),
            // coming back from sign-in, or a customer who already chose, goes straight to the chat
            'chosen' => $request->query('size') !== null,
            'loginUrl' => $request->user() ? null : route('login', ['redirect' => '/ai?size=big']),
            'projects' => $this->ownProjects($request),
        ]);
    }

    public function show(\Illuminate\Http\Request $request, BuilderProject $project): View
    {
        // whoever has the link and is signed in keeps the project in their account
        if ($request->user() && $project->user_id === null) {
            $project->update(['user_id' => $request->user()->id]);
        }

        $this->speakCustomerLanguage($project);

        return view('appbuilder::chat', ['state' => $this->stateOf($project), 'push' => \Modules\AppBuilder\Services\BuilderPush::webConfig()]);
    }

    /** Buttons and notes follow the language the customer writes in; a chat with no message yet keeps the site's. */
    private function speakCustomerLanguage(BuilderProject $project): void
    {
        if ($project->messages()->where('role', BuilderMessage::USER)->exists()) {
            app()->setLocale($project->customerLanguage());
        }
    }

    /** A screenshot the build sent to this chat; only images that belong to this project. */
    public function file(BuilderProject $project, \Modules\AppBuilder\Models\BuilderAttachment $attachment): \Illuminate\Http\Response
    {
        abort_unless($attachment->project_id === $project->id && $attachment->isImage(), 404);
        $data = $this->attachments->contents($attachment);
        abort_if($data === null, 404);

        return response($data, 200, [
            'Content-Type' => $attachment->mime,
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @return list<array{title: string, status: string, url: string}> */
    private function ownProjects(\Illuminate\Http\Request $request): array
    {
        if (! $request->user()) {
            return [];
        }

        return BuilderProject::where('user_id', $request->user()->id)->latest('updated_at')->limit(30)->get()
            ->map(fn (BuilderProject $project) => [
                'title' => $project->title ?: __('builder.untitled'),
                'status' => __('builder.status_labels.'.$project->status),
                'url' => route('builder.show', $project),
            ])->all();
    }

    public function state(BuilderProject $project, \Modules\AppBuilder\Services\LinkLooks $looks): JsonResponse
    {
        // the browser took too long with a shared link: the chat answers with what this server could read
        if ($looks->finishLateLooks($project)) {
            $this->chat->continueAfterLook($project);
        }
        $this->speakCustomerLanguage($project);

        return response()->json($this->stateOf($project));
    }

    /** The first message opens the chat, so visitors who only look never create a project. */
    public function start(SendMessageRequest $request): JsonResponse
    {
        try {
            $project = $this->chat->start((string) $request->ip(), app()->getLocale(), $this->referral->referrerFor($request), $request->user()?->id);
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
        if ($project->awaiting_look) {
            return $this->refuse('builder.errors.looking', 409);
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
