<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingProvider;
use Carbon\Carbon;

class BookingDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $providerIds = BookingProvider::where('host_user_id', $user->id)->pluck('id');
        
        $today = Carbon::today($user->timezone ?? config('app.timezone'));

        $totalBookings = Booking::whereIn('booking_provider_id', $providerIds)->count();
        $todayAppointments = Booking::whereIn('booking_provider_id', $providerIds)
            ->whereDate('starts_at', $today)
            ->count();
            
        $cancelledBookings = Booking::whereIn('booking_provider_id', $providerIds)
            ->where('status', 'cancelled')
            ->count();
            
        $revenue = Booking::whereIn('booking_provider_id', $providerIds)
            ->where('payment_status', 'paid')
            ->sum('price');
            
        $upcomingBookings = Booking::with(['provider', 'eventType'])
            ->whereIn('booking_provider_id', $providerIds)
            ->where('starts_at', '>=', Carbon::now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('starts_at', 'asc')
            ->take(5)
            ->get();

        return Inertia::render('Booking/Dashboard', [
            'stats' => [
                'total_bookings' => $totalBookings,
                'today_appointments' => $todayAppointments,
                'cancelled_bookings' => $cancelledBookings,
                'total_revenue' => $revenue,
            ],
            'upcoming_bookings' => $upcomingBookings,
        ]);
    }
}
