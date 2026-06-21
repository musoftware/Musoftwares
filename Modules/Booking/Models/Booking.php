<?php

namespace Modules\Booking\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Booking\Database\Factories\BookingFactory;

class Booking extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'booking_event_type_id',
        'client_user_id',
        'branch_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'starts_at',
        'ends_at',
        'timezone',
        'status',
        'payment_status',
        'price',
        'currency_id',
        'payment_method',
        'transaction_id',
        'notes',
        'internal_notes',
        'project_id',
        'recurring_series_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function eventType()
    {
        return $this->belongsTo(BookingEventType::class, 'booking_event_type_id');
    }

    public function provider()
    {
        return $this->belongsTo(BookingProvider::class, 'booking_provider_id');
    }

    public function clientUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'client_user_id');
    }

    public function branch()
    {
        return $this->belongsTo(BookingBranch::class, 'branch_id');
    }

    // Optional: link to project if applicable
    // public function project()
    // {
    //     return $this->belongsTo(\Modules\ERP\Models\Project::class, 'project_id');
    // }
}
