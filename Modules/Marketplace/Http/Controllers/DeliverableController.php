<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Services\DeliverableService;

class DeliverableController extends Controller
{
    public function __construct(protected DeliverableService $deliverableService) {}

    public function submitWork(Request $request, ServiceOrder $order)
    {
        if (auth()->id() !== $order->seller_id) {
            abort(403, 'Only seller can deliver work.');
        }

        $validated = $request->validate([
            'note' => 'required|string',
            'file' => 'nullable|file|max:50000',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('deliveries/' . $order->id, 'public');
        }

        try {
            $order = $this->deliverableService->submitDeliverable($order, $validated['note'], $filePath);

            if ($request->header('X-Inertia') || !$request->wantsJson()) {
                return back()->with('success', __('general.work_submitted_successfully'));
            }

            return response()->json(['success' => true, 'order' => $order]);
        } catch (\Exception $e) {
            if ($request->header('X-Inertia') || !$request->wantsJson()) {
                return back()->withErrors(['error' => $e->getMessage()]);
            }

            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function requestRevision(Request $request, ServiceOrder $order)
    {
        if (auth()->id() !== $order->buyer_id) {
            abort(403, 'Only buyer can request revisions.');
        }

        $validated = $request->validate([
            'revision_note' => 'required|string',
        ]);

        try {
            $order = $this->deliverableService->requestRevision($order, $validated['revision_note']);

            if ($request->header('X-Inertia') || !$request->wantsJson()) {
                return back()->with('success', __('general.revision_requested_successfully'));
            }

            return response()->json(['success' => true, 'order' => $order]);
        } catch (\Exception $e) {
            if ($request->header('X-Inertia') || !$request->wantsJson()) {
                return back()->withErrors(['error' => $e->getMessage()]);
            }

            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
