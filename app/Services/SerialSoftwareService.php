<?php

namespace App\Services;

use App\Models\SerialSoftware;

class SerialSoftwareService extends BaseService
{
    public function updateStatus(SerialSoftware $serialSoftware, string $status): void
    {
        $serialSoftware->update(['default_status' => $status]);
    }

    public function createSoftware(array $data): SerialSoftware
    {
        return SerialSoftware::create($data);
    }

    public function deleteSoftware(SerialSoftware $serialSoftware): void
    {
        $serialSoftware->delete();
    }
}
