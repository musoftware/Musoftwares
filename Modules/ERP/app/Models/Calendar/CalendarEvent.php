<?php

namespace Modules\ERP\Models\Calendar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\ERP\Database\Factories\Calendar/CalendarEventFactory;

class CalendarEvent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): Calendar/CalendarEventFactory
    // {
    //     // return Calendar/CalendarEventFactory::new();
    // }
}
