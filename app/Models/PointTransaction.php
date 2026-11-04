<?php

namespace App\Models;

use App\Models\Concerns\ScopesToLegalWindow;
use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    use ScopesToLegalWindow;

    protected $guarded = [];
}
