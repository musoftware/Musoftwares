<?php

namespace Modules\ERP\Services\Warehouse;

use Modules\ERP\Repositories\Warehouse\WarehouseBinRepository;
use Illuminate\Support\Facades\DB;

class WarehouseBinService
{
    protected WarehouseBinRepository $repository;

    public function __construct(WarehouseBinRepository $repository)
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
            $bin = $this->repository->findById($id);
            return $this->repository->update($bin, $data);
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $bin = $this->repository->findById($id);
            return $this->repository->delete($bin);
        });
    }
}
