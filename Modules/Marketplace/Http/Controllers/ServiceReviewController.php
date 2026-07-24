<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServiceReview;
use Modules\Marketplace\Enums\ServiceOrderStatus;

class ServiceReviewController extends Controller
{
    /**
     * Store a review for a completed order.
     * Only the buyer (client) of an order can review it.
     * One review per order.
     */
    public function store(Request $request, ServiceOrder $order): RedirectResponse
    {
        // Auth checks
        if ((int)$order->buyer_id !== (int)auth()->id()) {
            abort(403, __('general.only_the_buyer_can_leave_a_review'));
        }

        $orderStatusValue = $order->status instanceof ServiceOrderStatus ? $order->status->value : (string)$order->status;
        if ($orderStatusValue !== ServiceOrderStatus::COMPLETED->value) {
            return back()->with('error', __('general.you_can_only_review_completed_orders'));
        }

        // Prevent duplicate
        if (ServiceReview::where('order_id', $order->id)->where('reviewer_id', auth()->id())->exists()) {
            return back()->with('error', __('general.you_have_already_reviewed_this_order'));
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1500'],
        ]);

        $order->loadMissing('package');
        $serviceId = $order->package?->service_id ?? $order->service_id;

        ServiceReview::create([
            'service_id'  => $serviceId,
            'order_id'    => $order->id,
            'reviewer_id' => auth()->id(),
            'seller_id'   => $order->seller_id,
            'rating'      => $data['rating'],
            'review'      => $data['review'] ?? null,
            'is_public'   => true,
        ]);

        // Update aggregate rating on the service
        if ($serviceId) {
            ServiceReview::syncServiceRating($serviceId);
        }

        return back()->with('success', __('general.thank_you_for_your_review'));
    }

    /**
     * Soft-delete a review (owner or admin).
     */
    public function destroy(ServiceReview $review): RedirectResponse
    {
        if ($review->reviewer_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $serviceId = $review->service_id;
        $review->delete();
        ServiceReview::syncServiceRating($serviceId);

        return back()->with('success', __('general.review_removed'));
    }
}
