<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\ClientSystemSale;
use App\Models\Currency;
use App\Services\ClientSystems\LicenseIssuer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * The two calls a licensed client system makes (requests are signed, see VerifyClientSystemSignature):
 * check in for a fresh signed license, and report its sales for the share-of-sales bill.
 */
class ClientSystemController extends Controller
{
    public function checkIn(Request $request, LicenseIssuer $issuer): JsonResponse
    {
        $data = $request->validate(['fingerprint' => ['nullable', 'string', 'max:128'], 'version' => ['nullable', 'string', 'max:40']]);
        $system = $this->system($request);
        $this->recordCheckIn($system, $request->ip(), $data['fingerprint'] ?? null);

        return response()->json(['ok' => true, ...$issuer->issue($system, now())]);
    }

    public function reportSales(Request $request): JsonResponse
    {
        $system = $this->system($request);
        $data = $request->validate([
            'sales' => ['required', 'array', 'min:1', 'max:'.config('client_systems.max_sales_per_request')],
            'sales.*.id' => ['required', 'string', 'max:100', 'distinct'],
            'sales.*.amount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'sales.*.currency' => ['required', 'string', Rule::in([Currency::findCached($system->currency_id)?->currency])],
            'sales.*.sold_at' => ['required', 'date'],
            'sales.*.status' => ['required', Rule::in(ClientSystemSale::STATUSES)],
        ]);

        $billed = array_values(array_filter(array_map(fn (array $sale) => $this->saveSale($system, $sale), $data['sales'])));

        return response()->json(['ok' => true, 'accepted' => count($data['sales']) - count($billed), 'already_billed' => $billed]);
    }

    private function system(Request $request): ClientSystem
    {
        return $request->attributes->get('client_system');
    }

    /** Saves or updates one sale. A sale already on a bill cannot change; its id is returned instead. */
    private function saveSale(ClientSystem $system, array $sale): ?string
    {
        $existing = $system->sales()->where('external_id', $sale['id'])->first();
        if ($existing?->client_system_bill_id !== null) {
            return $sale['id'];
        }
        $system->sales()->updateOrCreate(
            ['external_id' => $sale['id']],
            ['amount' => $sale['amount'], 'status' => $sale['status'], 'sold_at' => Carbon::parse($sale['sold_at'])],
        );

        return null;
    }

    /** A changed fingerprint means the license code on their side was edited. It is kept for the admin to review. */
    private function recordCheckIn(ClientSystem $system, ?string $ip, ?string $fingerprint): void
    {
        $system->forceFill(['last_check_in_at' => now(), 'last_check_in_ip' => $ip]);
        if ($fingerprint !== null && $system->fingerprint === null) {
            $system->fingerprint = $fingerprint;
        }
        if ($fingerprint !== null && $fingerprint !== $system->fingerprint && $fingerprint !== $system->pending_fingerprint) {
            $system->forceFill(['pending_fingerprint' => $fingerprint, 'fingerprint_changed_at' => now()]);
            Log::warning('ClientSystems: license files changed on the client side', ['client_system_id' => $system->id, 'ip' => $ip]);
        }
        $system->save();
    }
}
