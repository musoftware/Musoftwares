<?php

namespace Modules\AppBuilder\Http\Controllers;

use App\Helpers\KashierHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Http\Requests\PayRequest;
use Modules\AppBuilder\Http\Requests\StudyPayRequest;
use Modules\AppBuilder\Services\Study\FeasibilityStudy;
use Modules\AppBuilder\Http\Resources\ProjectStateResource;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\ProjectPayment;

class BuilderPaymentController extends Controller
{
    public function __construct(private readonly ProjectPayment $payment) {}

    /** Answers with the Kashier link; the page then sends the customer there. */
    public function pay(PayRequest $request, BuilderProject $project): JsonResponse
    {
        if ($project->status !== BuilderProject::QUOTED && ! $project->hasBalanceDue()) {
            return response()->json(['message' => __('builder.errors.no_quote')], 409);
        }

        try {
            return response()->json(['url' => $this->payment->paymentUrl($project, $request->validated())]);
        } catch (\Throwable $e) {
            Log::error('App builder: payment start failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => __('builder.errors.payment')], 503);
        }
    }

    /** A signed-in customer pays from their site wallet; the build starts at once. */
    public function payWithWallet(Request $request, BuilderProject $project, \Modules\AppBuilder\Services\WalletPayment $wallet): JsonResponse
    {
        if (! $wallet->pay($project, $request->user())) {
            return response()->json(['message' => __('builder.errors.wallet')], 409);
        }

        return response()->json(new ProjectStateResource($project->refresh()->load('messages.attachments')));
    }

    /** A signed-in customer's first simple page, free: straight to the worker's queue with a small spending cap. */
    public function freeBuild(Request $request, BuilderProject $project): JsonResponse
    {
        if (! $this->payment->startFreeBuild($project, $request->user())) {
            return response()->json(['message' => __('builder.errors.no_free_build')], 409);
        }

        return response()->json(new ProjectStateResource($project->refresh()->load('messages.attachments')));
    }

    /** A free bug fix: no payment, straight to the worker's queue. */
    public function fix(BuilderProject $project): JsonResponse
    {
        if (! $this->payment->startFreeFix($project)) {
            return response()->json(['message' => __('builder.errors.no_quote')], 409);
        }

        return response()->json(new ProjectStateResource($project->refresh()->load('messages.attachments')));
    }

    public function webhook(Request $request): JsonResponse
    {
        if (! KashierHelper::validatePayload()) {
            Log::warning('App builder Kashier webhook: invalid signature.');

            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        $data = $request->json('data', []);
        $order = $this->payment->parseOrderId((string) ($data['merchantOrderId'] ?? ''));
        if (($data['status'] ?? '') !== 'SUCCESS' || ! $order) {
            return response()->json(['status' => 'ignored']);
        }

        $project = BuilderProject::find($order[0]);
        if (! $project) {
            return response()->json(['status' => 'error', 'message' => 'Project not found'], 404);
        }

        $order[2]
            ? app(FeasibilityStudy::class)->markPaid($project, $data['transactionId'] ?? null)
            : $this->payment->markPaid($project, $order[1], $data['transactionId'] ?? null);

        return response()->json(['status' => 'processed']);
    }

    /** Answers with the Kashier link for the feasibility study alone; the study is written once it is paid. */
    public function payStudy(StudyPayRequest $request, BuilderProject $project, FeasibilityStudy $study): JsonResponse
    {
        if (! $study->canBuy($project)) {
            return response()->json(['message' => __('builder.errors.no_study')], 409);
        }

        try {
            return response()->json(['url' => $this->payment->studyPaymentUrl($project, $request->validated())]);
        } catch (\Throwable $e) {
            Log::error('App builder: study payment start failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => __('builder.errors.payment')], 503);
        }
    }
}
