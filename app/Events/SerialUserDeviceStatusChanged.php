<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SerialUserDeviceStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $serialUserDevice;

    public $oldStatus;

    public $newStatus;

    public function __construct($serialUserDevice, $oldStatus, $newStatus)
    {
        $this->serialUserDevice = $serialUserDevice;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}
