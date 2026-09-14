<?php

namespace App\Services;

use App\Models\ClientVaultAsset;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ClientVaultService extends BaseService
{
    protected string $disk = 'local';

    /**
     * Get paginated vault assets for the user.
     */
    public function getUserAssets(User $user, ?string $assetType = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = ClientVaultAsset::with('project')
            ->where('user_id', $user->id)
            ->latest();

        if ($assetType) {
            $query->where('asset_type', $assetType);
        }

        return $query->paginate($perPage);
    }

    /**
     * Store a new vault asset for client lock-in.
     */
    public function storeAsset(User $user, array $data, ?UploadedFile $file = null): ClientVaultAsset
    {
        $storagePath = $data['storage_path'] ?? '';
        $mime = $data['file_mime_type'] ?? 'application/octet-stream';
        $size = (int) ($data['file_size_bytes'] ?? 0);

        if ($file) {
            $path = $file->store("client_vault/{$user->id}", $this->disk);
            $storagePath = $path;
            $mime = $file->getClientMimeType() ?: 'application/octet-stream';
            $size = $file->getSize();
        }

        return ClientVaultAsset::create([
            'user_id' => $user->id,
            'project_id' => $data['project_id'] ?? null,
            'title' => $data['title'],
            'asset_type' => $data['asset_type'],
            'storage_path' => $storagePath,
            'file_mime_type' => $mime,
            'file_size_bytes' => $size,
            'download_count' => 0,
        ]);
    }

    /**
     * Securely download a vault asset.
     */
    public function downloadAsset(User $user, ClientVaultAsset $asset): StreamedResponse|BinaryFileResponse
    {
        if ($asset->user_id !== $user->id) {
            throw new AccessDeniedHttpException('Unauthorized access to confidential vault asset.');
        }

        if (! Storage::disk($this->disk)->exists($asset->storage_path)) {
            throw new NotFoundHttpException('The requested asset file is not found in secure storage.');
        }

        $asset->increment('download_count');
        $asset->update(['last_accessed_at' => now()]);

        return Storage::disk($this->disk)->download(
            $asset->storage_path,
            $asset->title
        );
    }

    /**
     * Get summary metrics for the vault.
     */
    public function getVaultStats(User $user): array
    {
        $assets = ClientVaultAsset::where('user_id', $user->id)->get();

        return [
            'total_assets' => $assets->count(),
            'source_code_count' => $assets->where('asset_type', 'source_code')->count(),
            'builds_count' => $assets->where('asset_type', 'delivery_build')->count(),
            'invoices_count' => $assets->where('asset_type', 'final_invoice')->count(),
            'total_size_bytes' => $assets->sum('file_size_bytes'),
        ];
    }
}
