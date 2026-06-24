<?php

namespace Modules\ERP\Repositories\Warehouse;

use Modules\ERP\Models\Warehouse\WarehouseZone;

class WarehouseZoneRepository
{
    public function getAll(array $filters = [])
    {
        return WarehouseZone::query()
            ->with('warehouse')
            ->when(isset($filters['warehouse_id']), function ($q) use ($filters) {
                $q->where('warehouse_id', $filters['warehouse_id']);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return WarehouseZone::findOrFail($id);
    }

    public function create(array $data)
    {
        return WarehouseZone::create($data);
    }

    public function update(WarehouseZone $zone, array $data)
    {
        $zone->update($data);
        return $zone;
    }

    public function delete(WarehouseZone $zone)
    {
        return $zone->delete();
    }
}
