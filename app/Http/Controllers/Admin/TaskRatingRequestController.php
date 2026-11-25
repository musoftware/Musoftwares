<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceItem;
use App\Notifications\TaskRatingRequestNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Emails the client a link to rate one timed task. Their ratings raise the price of their next tasks. */
class TaskRatingRequestController extends Controller
{
    public function __invoke(InvoiceItem $item): RedirectResponse
    {
        $client = $item->invoice?->user;
        if (! $client?->email) {
            return redirect()->back()->with('error', __('service_pricing.no_client_email'));
        }

        $item->forceFill([
            'rating_token' => $item->rating_token ?? Str::random(32),
            'rating_requested_at' => now(),
        ])->save();

        try {
            $client->notify(new TaskRatingRequestNotification($item));
        } catch (\Throwable $e) {
            Log::error('Task rating request email failed.', ['invoice_item_id' => $item->id, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', __('service_pricing.rating_request_failed'));
        }

        return redirect()->back()->with('success', __('service_pricing.rating_request_sent'));
    }
}
