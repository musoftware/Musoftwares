<?php

namespace Modules\ERP\Services\Warehouse;

use Modules\ERP\Repositories\Warehouse\StockTransferRepository;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    protected StockTransferRepository $repository;

    public function __construct(StockTransferRepository $repository)
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
            $transfer = $this->repository->findById($id);
            return $this->repository->update($transfer, $data);
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $transfer = $this->repository->findById($id);
            return $this->repository->delete($transfer);
        });
    }
}
