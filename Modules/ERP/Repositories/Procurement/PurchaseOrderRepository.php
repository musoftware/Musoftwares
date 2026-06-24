<?php

namespace Modules\ERP\Repositories\Procurement;

use Modules\ERP\Models\Procurement\PurchaseOrder;

class PurchaseOrderRepository
{
    public function getAll(array $filters = [])
    {
        $query = PurchaseOrder::query()->with(['supplier', 'currency', 'items']);
        
        if (isset($filters['search'])) {
            $query->where('po_number', 'like', '%' . $filters['search'] . '%');
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return PurchaseOrder::with(['supplier', 'currency', 'items'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return PurchaseOrder::create($data);
    }

    public function update(PurchaseOrder $purchaseOrder, array $data)
    {
        $purchaseOrder->update($data);
        return $purchaseOrder;
    }

    public function delete(PurchaseOrder $purchaseOrder)
    {
        return $purchaseOrder->delete();
    }
}
