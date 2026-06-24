<?php

namespace Modules\ERP\Repositories\Asset;

use Modules\ERP\Models\Asset\AssetTransfer;

class AssetTransferRepository
{
    public function getAll(array $filters = [])
    {
        return AssetTransfer::query()
            ->with(['fixedAsset', 'fromEmployee', 'toEmployee'])
            ->when(isset($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return AssetTransfer::with(['fixedAsset', 'fromEmployee', 'toEmployee'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return AssetTransfer::create($data);
    }

    public function update(AssetTransfer $transfer, array $data)
    {
        $transfer->update($data);
        return $transfer;
    }

    public function delete(AssetTransfer $transfer)
    {
        return $transfer->delete();
    }
}
