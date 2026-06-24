<?php

namespace Modules\ERP\Repositories\Warehouse;

use Modules\ERP\Models\Warehouse\WarehouseBin;

class WarehouseBinRepository
{
    public function getAll(array $filters = [])
    {
        return WarehouseBin::query()
            ->with('zone.warehouse')
            ->when(isset($filters['warehouse_zone_id']), function ($q) use ($filters) {
                $q->where('warehouse_zone_id', $filters['warehouse_zone_id']);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return WarehouseBin::findOrFail($id);
    }

    public function create(array $data)
    {
        return WarehouseBin::create($data);
    }

    public function update(WarehouseBin $bin, array $data)
    {
        $bin->update($data);
        return $bin;
    }

    public function delete(WarehouseBin $bin)
    {
        return $bin->delete();
    }
}
