<?php

namespace Modules\ERP\Repositories\Warehouse;

use Modules\ERP\Models\Warehouse\StockAdjustment;

class StockAdjustmentRepository
{
    public function getAll(array $filters = [])
    {
        return StockAdjustment::query()
            ->with('warehouse')
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return StockAdjustment::findOrFail($id);
    }

    public function create(array $data)
    {
        return StockAdjustment::create($data);
    }

    public function update(StockAdjustment $adjustment, array $data)
    {
        $adjustment->update($data);
        return $adjustment;
    }

    public function delete(StockAdjustment $adjustment)
    {
        return $adjustment->delete();
    }
}
