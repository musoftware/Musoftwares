<?php

namespace App\Services;

use App\Models\SerialDevice;

class SerialDeviceService
{
    public function updateStatus(SerialDevice $serialDevice, string $status): void
    {
        $serialDevice->update(['status' => $status]);
    }

    public function deleteDevice(SerialDevice $serialDevice): void
    {
        $serialDevice->delete();
    }
}
