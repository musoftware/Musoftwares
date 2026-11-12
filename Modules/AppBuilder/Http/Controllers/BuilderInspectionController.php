<?php

namespace Modules\AppBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Http\Requests\BringProjectRequest;
use Modules\AppBuilder\Http\Requests\StudyPayRequest;
use Modules\AppBuilder\Http\Resources\ProjectStateResource;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\Chat\ChatLimitReached;
use Modules\AppBuilder\Services\ProjectInspection;
use Modules\AppBuilder\Services\ProjectPayment;

/** A customer brings a project of their own (a Google Drive link), pays the inspection fee, and gets the maintenance price. */
class BuilderInspectionController extends Controller
{
    use OpensChat;

    public function __construct(private readonly ProjectInspection $inspection) {}

    /** From the start page: a new chat that begins with the customer's project. */
    public function start(BringProjectRequest $request): JsonResponse
    {
        try {
            $project = $this->openChat($request);
        } catch (ChatLimitReached) {
            return response()->json(['message' => __('builder.errors.limit')], 429);
        }

        return $this->bring($request, $project);
    }

    /** In a chat that has not started a paid build yet. */
    public function bring(BringProjectRequest $request, BuilderProject $project): JsonResponse
    {
        if (! $this->inspection->canOffer($project)) {
            return response()->json(['message' => __('builder.inspection.not_now')], 409);
        }
        $this->inspection->offer($project, $request->validated('drive_url'), trim($request->validated('need')));

        return response()->json(new ProjectStateResource($project->refresh()->load('messages.attachments')));
    }

    /** Answers with the payment link for the inspection fee. */
    public function pay(StudyPayRequest $request, BuilderProject $project, ProjectPayment $payment): JsonResponse
    {
        if ($project->inspection_status !== ProjectInspection::OFFER) {
            return response()->json(['message' => __('builder.inspection.not_now')], 409);
        }

        try {
            return response()->json(['url' => $payment->inspectionPaymentUrl($project, $request->validated())]);
        } catch (\Throwable $e) {
            Log::error('App builder: inspection payment start failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => __('builder.errors.payment')], 503);
        }
    }

    /** Admins only: mark the fee paid by hand (or for a test); the inspection starts as after a real payment. */
    public function markPaid(\Illuminate\Http\Request $request, BuilderProject $project): JsonResponse
    {
        abort_unless($this->inspection->markPaid($project, 'admin-'.$request->user()->id.'-'.now()->timestamp), 409);

        return response()->json(new ProjectStateResource($project->refresh()->load('messages.attachments')));
    }
}
