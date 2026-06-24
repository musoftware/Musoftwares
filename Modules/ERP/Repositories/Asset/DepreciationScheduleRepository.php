<?php

namespace Modules\ERP\Repositories\Asset;

use Modules\ERP\Models\Asset\DepreciationSchedule;

class DepreciationScheduleRepository
{
    public function getAll(array $filters = [])
    {
        return DepreciationSchedule::query()
            ->with(['fixedAsset'])
            ->when(isset($filters['fixed_asset_id']), function ($q) use ($filters) {
                $q->where('fixed_asset_id', $filters['fixed_asset_id']);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return DepreciationSchedule::with(['fixedAsset'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return DepreciationSchedule::create($data);
    }

    public function update(DepreciationSchedule $schedule, array $data)
    {
        $schedule->update($data);
        return $schedule;
    }

    public function delete(DepreciationSchedule $schedule)
    {
        return $schedule->delete();
    }
}
