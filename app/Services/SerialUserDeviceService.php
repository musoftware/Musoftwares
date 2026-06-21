<?php

namespace App\Services;

use App\Models\SerialUserDevice;
use App\Models\User;

class SerialUserDeviceService extends BaseService
{

    public function assignDevice(array $data): SerialUserDevice
    {
        return SerialUserDevice::create($data);
    }

    public function updateStatus(SerialUserDevice $serialUserDevice, string $status): void
    {
        $serialUserDevice->update(['status' => $status]);
    }

    public function unassignDevice(SerialUserDevice $serialUserDevice): void
    {
        $serialUserDevice->delete();
    }

    public function updateUserStatus(User $user, string $status): void
    {
        // Iterate individually so the Observer fires for each device
        $user->serialUserDevices()->get()->each(
            fn($device) => $device->update(['status' => $status])
        );
    }

    public function updateUserTempValid(User $user, ?string $tempValidUntil): void
    {
        $user->update(['temp_valid_until' => $tempValidUntil]);
    }
}
