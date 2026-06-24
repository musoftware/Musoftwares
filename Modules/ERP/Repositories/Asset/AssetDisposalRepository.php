<?php

namespace Modules\ERP\Repositories\Asset;

use Modules\ERP\Models\Asset\AssetDisposal;

class AssetDisposalRepository
{
    public function getAll(array $filters = [])
    {
        return AssetDisposal::query()
            ->with(['fixedAsset'])
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return AssetDisposal::with(['fixedAsset'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return AssetDisposal::create($data);
    }

    public function update(AssetDisposal $disposal, array $data)
    {
        $disposal->update($data);
        return $disposal;
    }

    public function delete(AssetDisposal $disposal)
    {
        return $disposal->delete();
    }
}
