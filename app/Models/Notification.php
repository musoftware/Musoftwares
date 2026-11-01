<?php

namespace App\Models;

use App\Models\Concerns\ScopesToLegalWindow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use ScopesToLegalWindow, SoftDeletes;

    //
}
