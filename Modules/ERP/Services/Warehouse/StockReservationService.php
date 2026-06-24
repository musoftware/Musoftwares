<?php

namespace Modules\ERP\Services\Warehouse;

use Modules\ERP\Repositories\Warehouse\StockReservationRepository;
use Illuminate\Support\Facades\DB;

class StockReservationService
{
    protected StockReservationRepository $repository;

    public function __construct(StockReservationRepository $repository)
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
            $reservation = $this->repository->findById($id);
            return $this->repository->update($reservation, $data);
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $reservation = $this->repository->findById($id);
            return $this->repository->delete($reservation);
        });
    }
}
