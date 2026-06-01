<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantStorageProvider;
use Modules\ERP\Services\ActivityLogger;
use Inertia\Inertia;

class StorageProviderController extends Controller
{
    public function create()
    {
        return Inertia::render('ERP/StorageProviders/Create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return back()->withErrors(['error' => 'No active workspace found.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|string|in:s3,s3-cloudflare,s3-digitalocean,s3-wasabi',
            'key' => 'required|string',
            'secret' => 'required|string',
            'region' => 'nullable|string',
            'bucket' => 'required|string',
            'endpoint' => 'nullable|string',
        ]);

        $provider = TenantStorageProvider::updateOrCreate(
            ['tenant_id' => $tenant->id, 'is_default' => true],
            [
                'name' => $validated['name'],
                'driver' => $validated['driver'],
                'key' => $validated['key'],
                'secret' => $validated['secret'],
                'region' => $validated['region'],
                'bucket' => $validated['bucket'],
                'endpoint' => !empty($validated['endpoint']) ? $validated['endpoint'] : null,
                'is_default' => true,
            ]
        );

        ActivityLogger::log(
            'storage_configured',
            "AWS S3 Storage Provider '{$provider->name}' was configured.",
            $provider,
            null
        );

        return redirect()->route('erp.dashboard', ['section' => 'system'])->with('success', __('general.storage_provider_configured_successfully'));
    }
}
