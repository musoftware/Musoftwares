<?php

namespace Modules\ERP\Services\Warehouse;

use Modules\ERP\Repositories\Warehouse\WarehouseZoneRepository;
use Illuminate\Support\Facades\DB;

class WarehouseZoneService
{
    protected WarehouseZoneRepository $repository;

    public function __construct(WarehouseZoneRepository $repository)
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
            $zone = $this->repository->findById($id);
            return $this->repository->update($zone, $data);
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $zone = $this->repository->findById($id);
            return $this->repository->delete($zone);
        });
    }
}
