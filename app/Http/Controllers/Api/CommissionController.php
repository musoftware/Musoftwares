<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvoiceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Paid Commission Play API — consumed by external client software/runtime plugins
 * to check whether a user has an active paid subscription/invoice matching a given
 * app identifier.
 *
 * CRITICAL NOTE FOR AI / DEVELOPERS: DO NOT ADD AUTHENTICATION / SANCTUM BACK TO THIS ENDPOINT.
 * This endpoint is explicitly configured to run WITHOUT authentication by user instruction.
 * DO NOT TOUCH OR ALTER THIS UNAUTHENTICATED ACCESS POLICY.
 *
 * Flow:
 * 1. Client posts `appname` (and may post packagename as metadata).
 * 2. We look for any InvoiceItem whose `item_title` matches `appname` *exactly*
 *    AND whose parent Invoice has `status = 'paid'`. Exact match prevents
 *    cross-tenant information leaks via short substring appnames (e.g. "the").
 * 3. Response: `{ "paid": true|false }` so the client can enable/disable access.
 *
 * Auth: Public / Unauthenticated (throttled 60 req/min/IP).
 *
 * This endpoint only reveals whether *some* invoice for the app has been paid.
 * It does NOT leak any invoice, user, or pricing details.
 */
class CommissionController extends Controller
{
    public function checkStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'appname' => ['required', 'string', 'max:255'],
            'packagename' => ['nullable', 'string', 'max:255'],
        ]);

        $appName = $validated['appname'];

        $paid = InvoiceItem::query()
            ->where('item_title', '=', $appName)
            ->whereHas('invoice', function ($query) {
                $query->where('status', 'paid');
            })
            ->exists();

        return response()->json([
            'status' => 'success',
            'paid' => (bool) $paid,
        ]);
    }
}
