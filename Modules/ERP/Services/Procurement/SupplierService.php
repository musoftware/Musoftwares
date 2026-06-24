<?php

namespace Modules\ERP\Services\Procurement;

use Modules\ERP\Repositories\Procurement\SupplierRepository;
use Modules\ERP\Models\Procurement\Supplier;

class SupplierService
{
    protected $repository;

    public function __construct(SupplierRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllSuppliers(array $filters = [])
    {
        return $this->repository->getAll($filters);
    }

    public function getSupplierById(string $id)
    {
        return $this->repository->findById($id);
    }

    public function createSupplier(array $data)
    {
        return $this->repository->create($data);
    }

    public function updateSupplier(Supplier $supplier, array $data)
    {
        return $this->repository->update($supplier, $data);
    }

    public function deleteSupplier(Supplier $supplier)
    {
        return $this->repository->delete($supplier);
    }
}
