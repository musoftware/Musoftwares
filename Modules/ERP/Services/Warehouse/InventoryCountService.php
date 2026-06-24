<?php

namespace Modules\ERP\Services\Warehouse;

use Modules\ERP\Repositories\Warehouse\InventoryCountRepository;
use Illuminate\Support\Facades\DB;

class InventoryCountService
{
    protected InventoryCountRepository $repository;

    public function __construct(InventoryCountRepository $repository)
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
            $count = $this->repository->findById($id);
            return $this->repository->update($count, $data);
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $count = $this->repository->findById($id);
            return $this->repository->delete($count);
        });
    }
}
