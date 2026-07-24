<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SerialSoftware extends Model
{
    use SoftDeletes;

    protected $table = 'serial_softwares';

    protected $guarded = [];
}
