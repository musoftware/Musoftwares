<?php

namespace Modules\ERP\Repositories\Warehouse;

use Modules\ERP\Models\Warehouse\StockReservation;

class StockReservationRepository
{
    public function getAll(array $filters = [])
    {
        return StockReservation::query()
            ->with(['product', 'warehouse'])
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return StockReservation::findOrFail($id);
    }

    public function create(array $data)
    {
        return StockReservation::create($data);
    }

    public function update(StockReservation $reservation, array $data)
    {
        $reservation->update($data);
        return $reservation;
    }

    public function delete(StockReservation $reservation)
    {
        return $reservation->delete();
    }
}
