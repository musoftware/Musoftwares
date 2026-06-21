<?php

namespace Modules\Booking\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class BookingProvider extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'booking_providers';

    protected $fillable = [
        'host_user_id',
        'user_id',
        'name',
        'email',
        'phone',
        'specialty',
        'description',
        'avatar_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function host()
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function eventTypes()
    {
        return $this->belongsToMany(BookingEventType::class, 'booking_event_type_provider', 'booking_provider_id', 'booking_event_type_id');
    }

    public function availabilityRules()
    {
        return $this->hasMany(BookingAvailabilityRule::class, 'booking_provider_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'booking_provider_id');
    }
}
