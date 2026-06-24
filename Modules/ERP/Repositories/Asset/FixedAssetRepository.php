<?php

namespace Modules\ERP\Repositories\Asset;

use Modules\ERP\Models\Asset\FixedAsset;

class FixedAssetRepository
{
    public function getAll(array $filters = [])
    {
        return FixedAsset::query()
            ->with(['category', 'assignee'])
            ->when(isset($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return FixedAsset::with(['category', 'assignee'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return FixedAsset::create($data);
    }

    public function update(FixedAsset $asset, array $data)
    {
        $asset->update($data);
        return $asset;
    }

    public function delete(FixedAsset $asset)
    {
        return $asset->delete();
    }
}
