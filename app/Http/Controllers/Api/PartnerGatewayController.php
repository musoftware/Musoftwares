<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartnerClient;
use App\Services\PartnerGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PartnerGatewayController extends Controller
{
    public function __construct(
        protected PartnerGatewayService $gatewayService
    ) {}

    /**
     * Retrieve partner balance and account configuration.
     */
    public function balance(Request $request): JsonResponse
    {
        /** @var PartnerClient $client */
        $client = $request->attributes->get('partner_client');

        $data = $this->gatewayService->getBalanceInfo($client);

        return response()->json($data);
    }

    /**
     * Acquire a new credit lease by reserving wallet balance.
     */
    public function acquireLease(Request $request): JsonResponse
    {
        /** @var PartnerClient $client */
        $client = $request->attributes->get('partner_client');

        $validated = $request->validate([
            'requestedMessages' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'durationHours' => ['nullable', 'integer', 'min:1', 'max:72'],
        ]);

        $requestedMessages = (int) ($validated['requestedMessages'] ?? 500);
        $durationHours = (int) ($validated['durationHours'] ?? 2);

        try {
            $lease = $this->gatewayService->acquireLease($client, $requestedMessages, $durationHours);

            return response()->json($lease);
        } catch (RuntimeException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], $status);
        }
    }

    /**
     * Settle an active credit lease and optionally acquire a subsequent lease.
     */
    public function settleLease(Request $request): JsonResponse
    {
        /** @var PartnerClient $client */
        $client = $request->attributes->get('partner_client');

        $validated = $request->validate([
            'leaseId' => ['required', 'string', 'max:64'],
            'actualMessagesSent' => ['required', 'integer', 'min:0'],
            'requestNewLease' => ['nullable', 'boolean'],
            'newLeaseSize' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);

        try {
            $result = $this->gatewayService->settleLease(
                $client,
                $validated['leaseId'],
                (int) $validated['actualMessagesSent'],
                (bool) ($validated['requestNewLease'] ?? false),
                (int) ($validated['newLeaseSize'] ?? 500)
            );

            return response()->json($result);
        } catch (RuntimeException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], $status);
        }
    }
}
