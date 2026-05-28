<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantFile;
use Modules\ERP\Models\TenantStorageProvider;
use Modules\ERP\Services\ActivityLogger;

class FileController extends Controller
{
    private function configureS3Disk($provider)
    {
        Config::set('filesystems.disks.tenant_s3', [
            'driver' => 's3',
            'key' => $provider->key,
            'secret' => $provider->secret,
            'region' => $provider->region,
            'bucket' => $provider->bucket,
            'url' => null,
            'endpoint' => !empty($provider->endpoint) ? $provider->endpoint : null,
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ]);
    }

    public function create()
    {
        return inertia('ERP/Files/Create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return back()->withErrors(['error' => 'No active workspace found.']);
        }

        $provider = TenantStorageProvider::where('tenant_id', $tenant->id)->where('is_default', true)->first();
        if (!$provider) {
            return back()->withErrors(['error' => 'Please configure your AWS S3 Storage Provider first.']);
        }

        $request->validate([
            'file' => 'required|file',
            'type' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $type = $request->input('type', 'Document');
        $folder = 'erp/tenant_' . $tenant->id;

        try {
            $this->configureS3Disk($provider);

            $path = Storage::disk('tenant_s3')->putFileAs($folder, $file, time() . '_' . $originalName);

            $tenantFile = TenantFile::create([
                'tenant_id' => $tenant->id,
                'storage_provider_id' => $provider->id,
                'name' => $originalName,
                'path' => $path,
                'mime_type' => $mimeType,
                'size' => $size,
                'folder' => $type,
                'uploaded_by' => $user->id,
            ]);

            ActivityLogger::log(
                'file_uploaded',
                "File '{$originalName}' was uploaded.",
                $tenantFile,
                null
            );

            return back()->with('success', 'File uploaded successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'File upload failed: ' . $e->getMessage()]);
        }
    }

    public function show(TenantFile $file)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant || $file->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to file.');
        }

        $provider = $file->storageProvider;
        if (!$provider) {
            abort(404, 'Storage provider not found.');
        }

        try {
            $this->configureS3Disk($provider);
            $url = Storage::disk('tenant_s3')->temporaryUrl(
                $file->path, now()->addMinutes(30)
            );

            return redirect($url);
        } catch (\Exception $e) {
            abort(500, 'Failed to generate download URL: ' . $e->getMessage());
        }
    }

    public function destroy(TenantFile $file)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant || $file->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to file.');
        }

        $provider = $file->storageProvider;

        try {
            if ($provider) {
                $this->configureS3Disk($provider);
                if (Storage::disk('tenant_s3')->exists($file->path)) {
                    Storage::disk('tenant_s3')->delete($file->path);
                }
            }
            
            $fileName = $file->name;
            $file->delete();

            ActivityLogger::log(
                'file_deleted',
                "File '{$fileName}' was deleted.",
                null,
                null
            );

            return back()->with('success', 'File deleted successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'File deletion failed: ' . $e->getMessage()]);
        }
    }
}
