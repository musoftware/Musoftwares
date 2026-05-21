<?php

namespace App\Observers;

use Modules\Core\Models\SerialDevice;
use Modules\Core\Models\SerialUserDevice;

/**
 * Syncs status from SerialUserDevice → SerialDevice.
 *
 * Why this exists:
 * The API returns status from SerialDevice.
 * Admin manages status via SerialUserDevice (the assignment).
 * This observer keeps both in sync automatically.
 *
 * Important: Controllers use ->get()->each() instead of ->update()
 * so that model events fire per record and the Observer runs.
 */
class SerialUserDeviceObserver
{
    /**
     * When a device is assigned to a user for the first time,
     * sync the assignment status to the SerialDevice record.
     */
    public function created(SerialUserDevice $serialUserDevice): void
    {
        $this->syncStatusToSerialDevices($serialUserDevice);
    }

    /**
     * When the assignment status is changed by admin,
     * immediately propagate the new status to the SerialDevice record.
     * The device's software will see the change on its next API check-in.
     */
    public function updated(SerialUserDevice $serialUserDevice): void
    {
        if ($serialUserDevice->wasChanged('status')) {
            $this->syncStatusToSerialDevices($serialUserDevice);
        }
    }

    /**
     * When assignment is deleted, we leave SerialDevice status unchanged.
     * Admin can manually update the device status if needed.
     * Optionally uncomment the update below to auto-deactivate on unassign.
     */
    public function deleted(SerialUserDevice $serialUserDevice): void
    {
        // Optional: deactivate device when unassigned
        // SerialDevice::where('device_id', $serialUserDevice->device_id)
        //     ->update(['status' => SerialDevice::STATUS_INACTIVE]);
    }

    public function restored(SerialUserDevice $serialUserDevice): void
    {
        $this->syncStatusToSerialDevices($serialUserDevice);
    }

    public function forceDeleted(SerialUserDevice $serialUserDevice): void
    {
        // no-op
    }

    /**
     * Core sync: push the assignment status to ALL SerialDevice records
     * with the matching device_id (there should be one per software, but
     * using whereUpdate handles edge cases gracefully).
     */
    protected function syncStatusToSerialDevices(SerialUserDevice $serialUserDevice): void
    {
        SerialDevice::where('device_id', $serialUserDevice->device_id)
            ->update(['status' => $serialUserDevice->status]);
    }
}
