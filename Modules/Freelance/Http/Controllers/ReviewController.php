<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\Contract;
use Modules\Freelance\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function store(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        // Ensure user is part of the contract
        if ($contract->client_id !== $user->id && $contract->freelancer_id !== $user->id) {
            abort(403, 'You are not a participant in this contract.');
        }

        // Ensure contract is completed
        if ($contract->status !== 'completed') {
            return back()->with('error', 'You can only review completed contracts.');
        }

        // Determine reviewee
        $revieweeId = ($contract->client_id === $user->id) ? $contract->freelancer_id : $contract->client_id;

        // Check if user already reviewed
        $existingReview = Review::where('contract_id', $contract->id)
            ->where('reviewer_id', $user->id)
            ->first();

        if ($existingReview) {
            return back()->with('error', 'You have already submitted a review for this contract.');
        }

        // Create review
        $review = Review::create([
            'contract_id' => $contract->id,
            'reviewer_id' => $user->id,
            'reviewee_id' => $revieweeId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'is_visible' => false, // Blind rating by default
        ]);

        // Check if the OTHER party has already reviewed
        $otherReview = Review::where('contract_id', $contract->id)
            ->where('reviewer_id', $revieweeId)
            ->first();

        if ($otherReview) {
            // Both have reviewed! Make both visible.
            $review->update(['is_visible' => true]);
            $otherReview->update(['is_visible' => true]);
            return back()->with('success', __('freelance.review_submitted_visible'));
        }

        return back()->with('success', __('freelance.review_submitted_hidden'));
    }
}
