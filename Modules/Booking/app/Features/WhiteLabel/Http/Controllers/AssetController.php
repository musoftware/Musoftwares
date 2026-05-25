<?php

namespace Modules\Booking\app\Features\WhiteLabel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\WhiteLabel\Services\WhiteLabelAssetManager;
use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelAsset;

class AssetController extends Controller
{
    private WhiteLabelAssetManager $manager;

    public function __construct(WhiteLabelAssetManager $manager)
    {
        $this->manager = $manager;
    }

    public function index(Request $request)
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        return response()->json(WhiteLabelAsset::where('tenant_id', $tenantId)->get());
    }

    public function store(Request $request)
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;

        $request->validate([
            'type' => 'required|string|in:logo,favicon,email_banner',
            'file' => 'required|file|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        $asset = $this->manager->uploadAsset($tenantId, $request->file('file'), $request->input('type'));

        return response()->json($asset, 201);
    }

    public function destroy(Request $request, int $id)
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $asset = WhiteLabelAsset::where('tenant_id', $tenantId)->findOrFail($id);
        
        \Illuminate\Support\Facades\Storage::disk($asset->disk)->delete($asset->path);
        $asset->delete();

        return response()->json(null, 204);
    }
}
