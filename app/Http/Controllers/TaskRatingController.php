<?php

namespace App\Http\Controllers;

use App\Models\InvoiceItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** The client rates one task from the emailed link. The random code in the link is the only key. */
class TaskRatingController extends Controller
{
    public function show(string $token): Response
    {
        $item = $this->findByToken($token);

        return Inertia::render('Guest/TaskRating', [
            'task' => [
                'title' => $item->item_title,
                'invoice_number' => $item->invoice?->invoice_number,
                'client_name' => $item->invoice?->user?->name,
                'rating' => $item->client_rating,
                'note' => $item->client_rating_note,
            ],
            'submit_url' => route('task-rating.store', $token),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $item = $this->findByToken($token);
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'note' => 'nullable|string|max:1000',
        ]);

        $item->forceFill([
            'client_rating' => $data['rating'],
            'client_rating_note' => $data['note'] ?? null,
            'rated_at' => now(),
        ])->save();

        return redirect()->back()->with('success', __('service_pricing.thanks'));
    }

    private function findByToken(string $token): InvoiceItem
    {
        abort_unless(strlen($token) === 32, 404);

        return InvoiceItem::with('invoice.user')->where('rating_token', $token)->firstOrFail();
    }
}
