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

    public function updatePaymentSettings(SerialSoftware $serialSoftware, array $data): SerialSoftware
    {
        return $this->updateFullSettings($serialSoftware, $data);
    }

    public function updateFullSettings(SerialSoftware $serialSoftware, array $data): SerialSoftware
    {
        // Sync requires_payment flag with pricing_type
        if (isset($data['pricing_type'])) {
            $data['requires_payment'] = ($data['pricing_type'] !== SerialSoftware::PRICING_FREE);
        }

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

            // Deactivate devices for this software that don't have an active license
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

        return $serialSoftware;
    }

    public function createPackage(SerialSoftware $serialSoftware, array $data): \App\Models\SerialSoftwarePackage
    {
        if (! empty($data['is_default'])) {
            $serialSoftware->packages()->update(['is_default' => false]);
        }

        return $serialSoftware->packages()->create($data);
    }

    public function updatePackage(\App\Models\SerialSoftwarePackage $package, array $data): \App\Models\SerialSoftwarePackage
    {
        if (! empty($data['is_default'])) {
            $package->software->packages()->where('id', '!=', $package->id)->update(['is_default' => false]);
        }

        $package->update($data);

        return $package;
    }

    public function deletePackage(\App\Models\SerialSoftwarePackage $package): void
    {
        $package->delete();
    }

    public function deleteSoftware(SerialSoftware $serialSoftware): void
    {
        $serialSoftware->delete();
    }
}
