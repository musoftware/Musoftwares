<?php

namespace Modules\ERP\Repositories\Warehouse;

use Modules\ERP\Models\Warehouse\Warehouse;

class WarehouseRepository
{
    public function getAll(array $filters = [])
    {
        return Warehouse::query()
            ->when(isset($filters['is_active']), function ($q) use ($filters) {
                $q->where('is_active', $filters['is_active']);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return Warehouse::findOrFail($id);
    }

    public function create(array $data)
    {
        return Warehouse::create($data);
    }

    public function update(Warehouse $warehouse, array $data)
    {
        $warehouse->update($data);
        return $warehouse;
    }

    public function delete(Warehouse $warehouse)
    {
        return $warehouse->delete();
    }
}
