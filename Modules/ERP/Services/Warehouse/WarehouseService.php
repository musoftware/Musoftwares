<?php

namespace Modules\ERP\Services\Warehouse;

use Modules\ERP\Repositories\Warehouse\WarehouseRepository;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    protected WarehouseRepository $repository;

    public function __construct(WarehouseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(array $filters = [])
    {
        return $this->repository->getAll($filters);
    }

    public function findById(string $id)
    {
        return $this->repository->findById($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function update(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $warehouse = $this->repository->findById($id);
            return $this->repository->update($warehouse, $data);
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $warehouse = $this->repository->findById($id);
            return $this->repository->delete($warehouse);
        });
    }

    public function initiateTransfer(
        int $tenantId,
        string $fromWarehouseId,
        string $toWarehouseId,
        int $productId,
        float $quantity,
        string $notes = null
    ) {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new \Exception("Cannot transfer to the same warehouse.");
        }

        return DB::transaction(function () use ($tenantId, $fromWarehouseId, $toWarehouseId, $productId, $quantity, $notes) {
            $transfer = \Modules\ERP\Models\Warehouse\StockTransfer::create([
                'tenant_id' => $tenantId,
                'reference_number' => 'TRF-' . time(),
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'status' => 'completed',
                'transfer_date' => now(),
                'notes' => $notes,
            ]);

            \Modules\ERP\Models\ProductStockLog::create([
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'type' => 'reduction',
                'quantity' => $quantity,
                'description' => "Transfer out to warehouse " . $toWarehouseId,
            ]);

            \Modules\ERP\Models\ProductStockLog::create([
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'type' => 'addition',
                'quantity' => $quantity,
                'description' => "Transfer in from warehouse " . $fromWarehouseId,
            ]);

            return $transfer;
        });
    }
}
