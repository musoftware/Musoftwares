<?php

namespace Modules\ERP\Services\Procurement;

use Modules\ERP\Repositories\Procurement\PurchaseOrderRepository;
use Modules\ERP\Models\Procurement\PurchaseOrder;

class PurchaseOrderService
{
    protected $repository;

    public function __construct(PurchaseOrderRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(array $filters = [])
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id)
    {
        return $this->repository->findById($id);
    }

    public function create(array $data)
    {
        // Business logic like calculating totals could go here
        return $this->repository->create($data);
    }

    public function update(PurchaseOrder $purchaseOrder, array $data)
    {
        return $this->repository->update($purchaseOrder, $data);
    }

    public function delete(PurchaseOrder $purchaseOrder)
    {
        return $this->repository->delete($purchaseOrder);
    }
}
