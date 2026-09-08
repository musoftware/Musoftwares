<?php

use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwareLicense;
use App\Models\SerialUserDevice;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For all softwares marked as requires_payment = true,
        // deactivate any devices that do not have an active license.
        $paidSoftwares = SerialSoftware::where('requires_payment', true)->get();

        foreach ($paidSoftwares as $software) {
            $licensedUserIds = SerialSoftwareLicense::where('serial_software_id', $software->id)
                ->active()
                ->pluck('user_id')
                ->toArray();

            $licensedDeviceIds = SerialUserDevice::whereIn('user_id', $licensedUserIds)
                ->pluck('device_id')
                ->toArray();

            SerialDevice::where('serial_software_id', $software->id)
                ->whereNotIn('device_id', $licensedDeviceIds)
                ->update(['status' => SerialDevice::STATUS_INACTIVE]);

            SerialUserDevice::whereNotIn('device_id', $licensedDeviceIds)
                ->whereIn('device_id', function ($query) use ($software) {
                    $query->select('device_id')
                        ->from('serial_devices')
                        ->where('serial_software_id', $software->id);
                })
                ->update(['status' => SerialUserDevice::STATUS_INACTIVE]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
