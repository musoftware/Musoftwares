<?php

namespace Modules\ERP\Services\Warehouse;

use Modules\ERP\Repositories\Warehouse\StockAdjustmentRepository;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    protected StockAdjustmentRepository $repository;

    public function __construct(StockAdjustmentRepository $repository)
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
            $adjustment = $this->repository->findById($id);
            return $this->repository->update($adjustment, $data);
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $adjustment = $this->repository->findById($id);
            return $this->repository->delete($adjustment);
        });
    }
}
