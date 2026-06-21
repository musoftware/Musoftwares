<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestTicket extends Model
{
    use SoftDeletes, HasFactory;

    protected $guarded = ['id'];
}
