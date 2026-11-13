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
use Modules\AppBuilder\Services\PaymentOrders;
use Modules\AppBuilder\Services\ProjectInspection;
use Modules\AppBuilder\Services\ProjectPayment;
use Modules\AppBuilder\Services\ProjectRequests;

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

    /** Answers with the Kashier link for the requests the customer ticked in the list. */
    public function payRequests(\Modules\AppBuilder\Http\Requests\RequestsPayRequest $request, BuilderProject $project, \Modules\AppBuilder\Services\ProjectRequests $requests): JsonResponse
    {
        $chosen = $requests->unpaid($project, $request->ids());
        if ($chosen->isEmpty()) {
            return response()->json(['message' => __('builder.errors.no_requests')], 409);
        }

        try {
            return response()->json(['url' => $this->payment->requestsPaymentUrl($project, $request->client(), $chosen)]);
        } catch (\Throwable $e) {
            Log::error('App builder: requests payment start failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => __('builder.errors.payment')], 503);
        }
    }

    /** The customer takes back a request that has not started; a paid one waiting in line keeps its money as a note for the owner. */
    public function cancelRequest(BuilderProject $project, int $item, \Modules\AppBuilder\Services\ProjectRequests $requests): JsonResponse
    {
        $request = $project->requests()->whereKey($item)->first();
        if (! $request || ! $requests->cancel($project, $item)) {
            return response()->json(['message' => __('builder.errors.request_started')], 409);
        }
        if ($request->paid_at !== null) {
            $this->payment->tellOwner($project, 'Paid request cancelled by the customer (refund or credit it): '.$request->title.' '.$request->amount.' '.$project->currency);
        }

        return response()->json(new ProjectStateResource($project->refresh()->load('messages.attachments')));
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
        if (($data['status'] ?? '') !== 'SUCCESS') {
            return response()->json(['status' => 'ignored']);
        }
        // the link this payment was made from: it says exactly how much was paid, and it pays only once
        $paidOrder = app(PaymentOrders::class)->claim($data);
        if (! $paidOrder) {
            $this->tellOwnerUnmatched((string) ($data['merchantOrderId'] ?? ''));

            return response()->json(['status' => 'ignored']);
        }

        $project = $paidOrder->project;
        $orderId = $paidOrder->order_id;
        $amount = (float) $paidOrder->amount;
        $transaction = $data['transactionId'] ?? null;
        match (true) {
            ($paid = $this->payment->parseRequestsOrder($orderId)) !== null => app(ProjectRequests::class)->markPaid($project, $paid[1], $transaction, $amount),
            $this->payment->parseInspectionOrder($orderId) !== null => app(ProjectInspection::class)->markPaid($project, $transaction),
            ($order = $this->payment->parseOrderId($orderId)) === null => Log::warning('App builder: paid link of an unknown kind', ['order' => $orderId]),
            $order[2] => app(FeasibilityStudy::class)->markPaid($project, $transaction),
            default => $this->payment->markPaid($project, $order[1], $transaction, $amount),
        };

        return response()->json(['status' => 'processed']);
    }

    /** A real payment that matched no open link is never lost silently: the owner checks it in Kashier. */
    private function tellOwnerUnmatched(string $orderId): void
    {
        $project = preg_match('/^'.ProjectPayment::ORDER_PREFIX.'(\d+)-/', $orderId, $m) ? BuilderProject::find((int) $m[1]) : null;
        $project && $this->payment->tellOwner($project, 'A Kashier payment was not credited (unknown, repeated or wrong amount): '.$orderId.'. Check it in Kashier.');
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
