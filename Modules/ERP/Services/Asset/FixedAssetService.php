<?php

namespace Modules\ERP\Services\Asset;

use Modules\ERP\Repositories\Asset\FixedAssetRepository;

class FixedAssetService
{
    protected $repository;

    public function __construct(FixedAssetRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllAssets(array $filters = [])
    {
        return $this->repository->getAll($filters);
    }

    public function getAssetById(string $id)
    {
        return $this->repository->findById($id);
    }

    public function createAsset(array $data)
    {
        return $this->repository->create($data);
    }

    public function updateAsset(string $id, array $data)
    {
        $asset = $this->repository->findById($id);
        return $this->repository->update($asset, $data);
    }

    public function deleteAsset(string $id)
    {
        $asset = $this->repository->findById($id);
        return $this->repository->delete($asset);
    }
}
