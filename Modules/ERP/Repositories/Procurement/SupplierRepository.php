<?php

namespace Modules\ERP\Repositories\Procurement;

use Modules\ERP\Models\Procurement\Supplier;

class SupplierRepository
{
    public function getAll(array $filters = [])
    {
        $query = Supplier::query();
        
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%');
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return Supplier::findOrFail($id);
    }

    public function create(array $data)
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data)
    {
        $supplier->update($data);
        return $supplier;
    }

    public function delete(Supplier $supplier)
    {
        return $supplier->delete();
    }
}
