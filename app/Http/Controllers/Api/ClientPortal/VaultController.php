<?php

namespace App\Http\Controllers\Api\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\ClientVaultAsset;
use App\Services\ClientVaultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VaultController extends Controller
{
    public function __construct(
        protected ClientVaultService $vaultService
    ) {}

    /**
     * List confidential vault assets owned by the client.
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $assets = $this->vaultService->getUserAssets($request->user(), $type);

        return response()->json([
            'status' => 'success',
            'data' => $assets,
        ]);
    }

    /**
     * Get summary metrics for the vault.
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->vaultService->getVaultStats($request->user());

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * Download a confidential asset with ownership verification and audit increment.
     */
    public function download(Request $request, ClientVaultAsset $asset): Response
    {
        return $this->vaultService->downloadAsset($request->user(), $asset);
    }
}
