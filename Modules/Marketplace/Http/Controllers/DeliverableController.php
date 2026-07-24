<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Services\DeliverableService;

class DeliverableController extends Controller
{
    public function __construct(protected DeliverableService $deliverableService) {}

    public function submitWork(Request $request, ServiceOrder $order): RedirectResponse|JsonResponse
    {
        if ($request->user()->id !== $order->seller_id) {
            abort(403, 'Only seller can deliver work.');
        }

        $validated = $request->validate([
            'note' => 'required|string',
            'file' => 'nullable|file|max:50000',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('deliveries/'.$order->id, 'public');
        }

        try {
            $updatedOrder = $this->deliverableService->submitDeliverable($order, $validated['note'], $filePath);

            return $this->respondSuccess($request, __('general.work_submitted_successfully'), ['order' => $updatedOrder]);
        } catch (\Exception $e) {
            return $this->respondError($request, $e->getMessage());
        }
    }

    public function requestRevision(Request $request, ServiceOrder $order): RedirectResponse|JsonResponse
    {
        if ($request->user()->id !== $order->buyer_id) {
            abort(403, 'Only buyer can request revisions.');
        }

        $validated = $request->validate([
            'revision_note' => 'required|string',
        ]);

        try {
            $updatedOrder = $this->deliverableService->requestRevision($order, $validated['revision_note']);

            return $this->respondSuccess($request, __('general.revision_requested_successfully'), ['order' => $updatedOrder]);
        } catch (\Exception $e) {
            return $this->respondError($request, $e->getMessage());
        }
    }

    private function respondSuccess(Request $request, string $message, array $extra = []): RedirectResponse|JsonResponse
    {
        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return back()->with('success', $message);
        }

        return response()->json(array_merge(['success' => true], $extra));
    }

    private function respondError(Request $request, string $error): RedirectResponse|JsonResponse
    {
        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return back()->withErrors(['error' => $error]);
        }

        return response()->json(['success' => false, 'error' => $error], 422);
    }
}

