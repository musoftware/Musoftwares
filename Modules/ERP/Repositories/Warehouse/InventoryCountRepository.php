<?php

namespace Modules\ERP\Repositories\Warehouse;

use Modules\ERP\Models\Warehouse\InventoryCount;

class InventoryCountRepository
{
    public function getAll(array $filters = [])
    {
        return InventoryCount::query()
            ->with('warehouse')
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return InventoryCount::findOrFail($id);
    }

    public function create(array $data)
    {
        return InventoryCount::create($data);
    }

    public function update(InventoryCount $count, array $data)
    {
        $count->update($data);
        return $count;
    }

    public function delete(InventoryCount $count)
    {
        return $count->delete();
    }
}
