<?php

namespace Modules\ERP\Services\Asset;

use Modules\ERP\Repositories\Asset\AssetCategoryRepository;

class AssetCategoryService
{
    protected $repository;

    public function __construct(AssetCategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllCategories(array $filters = [])
    {
        return $this->repository->getAll($filters);
    }

    public function getCategoryById(string $id)
    {
        return $this->repository->findById($id);
    }

    public function createCategory(array $data)
    {
        return $this->repository->create($data);
    }

    public function updateCategory(string $id, array $data)
    {
        $category = $this->repository->findById($id);
        return $this->repository->update($category, $data);
    }

    public function deleteCategory(string $id)
    {
        $category = $this->repository->findById($id);
        return $this->repository->delete($category);
    }
}
