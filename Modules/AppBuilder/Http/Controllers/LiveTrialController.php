<?php

namespace Modules\AppBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AppBuilder\Http\Resources\ProjectStateResource;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\LiveTrials;

/** The customer asks for a live trial; the worker picks requests up and reports the link. */
class LiveTrialController extends Controller
{
    public function __construct(private readonly LiveTrials $trials) {}

    public function request(BuilderProject $project): JsonResponse
    {
        if (! $this->trials->request($project)) {
            return response()->json(['message' => __('builder.errors.trial')], 409);
        }

        return response()->json(new ProjectStateResource($project->refresh()->load('messages.attachments')));
    }

    /** Worker: the trials to start now. */
    public function pending(Request $request): JsonResponse
    {
        if (! app(\Modules\AppBuilder\Services\WorkerRoles::class)->mayTakeNewWork($request)) {
            return response()->json(['trials' => []]);
        }

        return response()->json(['trials' => $this->trials->pending()]);
    }

    public function started(Request $request, BuilderProject $project): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url:https', 'max:500'],
            'password' => ['required', 'string', 'max:64'],
            'minutes' => ['required', 'integer', 'min:5', 'max:240'],
        ]);
        $this->trials->started($project, $data['url'], $data['password'], (int) $data['minutes']);

        return response()->json(['ok' => true]);
    }

    public function failed(BuilderProject $project): JsonResponse
    {
        $this->trials->failed($project);

        return response()->json(['ok' => true]);
    }
}
