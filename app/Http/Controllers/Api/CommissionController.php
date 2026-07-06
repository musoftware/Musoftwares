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
 * Flow:
 * 1. Client posts `appname` (and may post packagename / commissionRate as metadata).
 * 2. We look for any InvoiceItem whose `item_title` LIKE %appname% and whose parent
 *    Invoice has `status = 'paid'`.
 * 3. Response: `{ "paid": true|false }` so the client can enable/disable access.
 *
 * This endpoint is intentionally unauthenticated and read-only: it only reveals
 * whether *some* invoice for the app has been paid. It does NOT leak any invoice,
 * user, or pricing details.
 */
class CommissionController extends Controller
{
    public function checkStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'appname'     => ['required', 'string', 'max:255'],
            'packagename' => ['nullable', 'string', 'max:255'],
        ]);

        $appName = $validated['appname'];

        $paid = InvoiceItem::query()
            ->where('item_title', 'like', '%'.$appName.'%')
            ->whereHas('invoice', function ($query) {
                $query->where('status', 'paid');
            })
            ->exists();

        return response()->json([
            'status' => 'success',
            'paid'   => (bool) $paid,
        ]);
    }
}
