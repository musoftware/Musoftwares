<?php

namespace Modules\ERP\Repositories\Asset;

use Modules\ERP\Models\Asset\AssetCategory;

class AssetCategoryRepository
{
    public function getAll(array $filters = [])
    {
        return AssetCategory::query()
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id)
    {
        return AssetCategory::findOrFail($id);
    }

    public function create(array $data)
    {
        return AssetCategory::create($data);
    }

    public function update(AssetCategory $category, array $data)
    {
        $category->update($data);
        return $category;
    }

    public function delete(AssetCategory $category)
    {
        return $category->delete();
    }
}
