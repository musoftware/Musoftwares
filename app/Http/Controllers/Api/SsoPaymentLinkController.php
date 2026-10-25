<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PlanPaymentLinkService;
use App\Support\SsoSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Server-to-server: a satellite system asks for a guest-payable link for one of
 * its plans. The caller never sends a price — the monolith owns it.
 */
class SsoPaymentLinkController extends Controller
{
    public const SIGNATURE_ACTION = 'payment-links-create';

    public function __construct(private readonly PlanPaymentLinkService $links) {}

    public function store(Request $request): JsonResponse
    {
        $system = SsoSignature::systemFromHeaders($request);
        $signatureError = SsoSignature::verify($request, $system, self::SIGNATURE_ACTION);
        if ($signatureError !== null) {
            return response()->json(['error' => $signatureError], 401);
        }

        $data = $request->validate([
            'plan' => ['required', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer'],
            'return_url' => ['nullable', 'url', 'max:500'],
        ]);

        $plan = $this->links->plan($data['plan']);
        if (! $plan || $plan['system'] !== $system) {
            return response()->json(['error' => 'unknown_plan'], 422);
        }

        $client = isset($data['user_id']) ? User::find($data['user_id']) : null;
        $link = $this->links->create($plan, $client, $this->trustedReturnUrl($system, $data['return_url'] ?? null));

        return response()->json([
            'url' => route('guest.payment-links.show', ['uuid' => $link->uuid]),
            'uuid' => $link->uuid,
            'amount' => (string) $link->amount,
            'currency' => (string) $plan['currency'],
            'expires_at' => $link->expires_at?->toIso8601String(),
        ], 201);
    }

    /**
     * Only send payers back to the calling system's own host (no open redirect).
     */
    private function trustedReturnUrl(?string $system, ?string $url): ?string
    {
        $systemHost = parse_url((string) config("services.{$system}.url"), PHP_URL_HOST);

        return $url !== null && $systemHost && parse_url($url, PHP_URL_HOST) === $systemHost ? $url : null;
    }
}
