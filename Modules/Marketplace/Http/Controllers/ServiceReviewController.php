<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServiceReview;

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
        if ($order->client_id !== auth()->id()) {
            abort(403, 'Only the buyer can leave a review.');
        }

        if ($order->status !== 'completed') {
            return back()->with('error', 'You can only review completed orders.');
        }

        // Prevent duplicate
        if (ServiceReview::where('order_id', $order->id)->where('reviewer_id', auth()->id())->exists()) {
            return back()->with('error', 'You have already reviewed this order.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1500'],
        ]);

        ServiceReview::create([
            'service_id'  => $order->service_id,
            'order_id'    => $order->id,
            'reviewer_id' => auth()->id(),
            'seller_id'   => $order->seller_id,
            'rating'      => $data['rating'],
            'review'      => $data['review'] ?? null,
            'is_public'   => true,
        ]);

        // Update aggregate rating on the service
        ServiceReview::syncServiceRating($order->service_id);

        return back()->with('success', 'Thank you for your review!');
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

        return back()->with('success', 'Review removed.');
    }
}
