<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Services\PremiumToolService;

class PremiumToolController extends Controller
{
    public function __construct(protected PremiumToolService $premiumToolService) {}

    public function useTool(Request $request, string $toolSlug)
    {
        $validated = $request->validate([
            'prompt' => 'nullable|string',
            'params' => 'nullable|array',
        ]);

        try {
            $result = $this->premiumToolService->executeTool(auth()->user(), $toolSlug, $validated);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
