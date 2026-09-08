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

    public function updatePaymentSettings(SerialSoftware $serialSoftware, array $data): void
    {
        $serialSoftware->update($data);

        if (! empty($data['requires_payment'])) {
            // Find all users with an active license for this software
            $licensedUserIds = \App\Models\SerialSoftwareLicense::where('serial_software_id', $serialSoftware->id)
                ->active()
                ->pluck('user_id')
                ->toArray();

            // Find device_ids linked to these licensed users
            $licensedDeviceIds = \App\Models\SerialUserDevice::whereIn('user_id', $licensedUserIds)
                ->pluck('device_id')
                ->toArray();

            // Deactivate all devices for this software that don't have an active license
            \App\Models\SerialDevice::where('serial_software_id', $serialSoftware->id)
                ->whereNotIn('device_id', $licensedDeviceIds)
                ->update(['status' => \App\Models\SerialDevice::STATUS_INACTIVE]);

            // Deactivate user device assignments as well
            \App\Models\SerialUserDevice::whereNotIn('device_id', $licensedDeviceIds)
                ->whereIn('device_id', function ($query) use ($serialSoftware) {
                    $query->select('device_id')
                        ->from('serial_devices')
                        ->where('serial_software_id', $serialSoftware->id);
                })
                ->update(['status' => \App\Models\SerialUserDevice::STATUS_INACTIVE]);
        }
    }

    public function deleteSoftware(SerialSoftware $serialSoftware): void
    {
        $serialSoftware->delete();
    }
}
