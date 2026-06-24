<?php

namespace Modules\ERP\Repositories\Warehouse;

use Modules\ERP\Models\Warehouse\StockTransfer;

class StockTransferRepository
{
    public function getAll(array $filters = [])
    {
        return StockTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse'])
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return StockTransfer::findOrFail($id);
    }

    public function create(array $data)
    {
        return StockTransfer::create($data);
    }

    public function update(StockTransfer $transfer, array $data)
    {
        $transfer->update($data);
        return $transfer;
    }

    public function delete(StockTransfer $transfer)
    {
        return $transfer->delete();
    }
}
