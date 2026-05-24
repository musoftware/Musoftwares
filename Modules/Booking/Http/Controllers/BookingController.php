<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingEventType;
use Modules\Booking\Models\BookingProvider;
use Modules\Booking\Models\BookingAvailabilityRule;
use Modules\Booking\Models\BookingBlockedDate;
use App\Models\User;
use App\Models\Transaction;
use App\Helpers\KashierHelper;
use Inertia\Inertia;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Display a listing of bookings for the user (host view).
     */
    public function index()
    {
        $user = Auth::user();
        $bookings = Booking::with(['eventType', 'clientUser', 'provider'])
            ->whereHas('eventType', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest()
            ->paginate(15);
            
        return Inertia::render('Booking/Index', [
            'bookings' => $bookings
        ]);
    }

    /**
     * Public page to book an event.
     */
    public function showPublic($username, $slug)
    {
        $host = User::where('name', $username)->firstOrFail();
        
        // Eager load providers performing this event
        $eventType = BookingEventType::where('user_id', $host->id)
            ->where('slug', $slug)
            ->with(['providers' => function ($q) {
                $q->where('is_active', true);
            }])
            ->firstOrFail();

        return Inertia::render('Booking/Public/Show', [
            'host' => $host->only('id', 'name', 'avatar'),
            'eventType' => $eventType
        ]);
    }

    /**
     * Get available slots for a given event type and date.
     */
    public function getSlotsApi(Request $request)
    {
        $request->validate([
            'event_type_id' => 'required|exists:booking_event_types,id',
            'date' => 'required|date_format:Y-m-d',
            'provider_id' => 'nullable|exists:booking_providers,id'
        ]);

        $eventTypeId = $request->event_type_id;
        $dateStr = $request->date;
        $providerId = $request->provider_id;

        $eventType = BookingEventType::findOrFail($eventTypeId);
        
        $providersQuery = $eventType->providers()->where('is_active', true);
        if ($providerId) {
            $providersQuery->where('booking_providers.id', $providerId);
        }
        $providers = $providersQuery->get();

        $targetDate = Carbon::parse($dateStr);
        $weekday = $targetDate->dayOfWeek; // 0 (Sunday) to 6 (Saturday)

        $slots = [];

        // Fetch host blocked dates on this day
        $blockedDates = BookingBlockedDate::where('user_id', $eventType->user_id)
            ->where(function($q) use ($targetDate) {
                $startOfDay = $targetDate->copy()->startOfDay();
                $endOfDay = $targetDate->copy()->endOfDay();
                $q->whereBetween('starts_at', [$startOfDay, $endOfDay])
                  ->orWhereBetween('ends_at', [$startOfDay, $endOfDay])
                  ->orWhere(function($sub) use ($startOfDay, $endOfDay) {
                      $sub->where('starts_at', '<=', $startOfDay)
                          ->where('ends_at', '>=', $endOfDay);
                  });
            })
            ->get();

        if ($providers->isEmpty() && !$providerId) {
            // Backwards compatibility fallback: no providers assigned, use event-type level rules
            $rules = BookingAvailabilityRule::where('booking_event_type_id', $eventType->id)
                ->where('type', 'recurring')
                ->where('weekday', $weekday)
                ->where('is_enabled', true)
                ->get();
                
            if (!$rules->isEmpty()) {
                $existingBookings = Booking::where('booking_event_type_id', $eventType->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('starts_at', $dateStr)
                    ->get();
                    
                $duration = $eventType->duration_minutes;
                $bufferBefore = $eventType->buffer_before ?? 0;
                $bufferAfter = $eventType->buffer_after ?? 0;
                
                foreach ($rules as $rule) {
                    $start = Carbon::parse($dateStr . ' ' . $rule->start_time);
                    $end = Carbon::parse($dateStr . ' ' . $rule->end_time);
                    $current = $start->copy();
                    
                    while ($current->copy()->addMinutes($duration)->lte($end)) {
                        $slotStart = $current->copy();
                        $slotEnd = $slotStart->copy()->addMinutes($duration);
                        
                        $windowStart = $slotStart->copy()->subMinutes($bufferBefore);
                        $windowEnd = $slotEnd->copy()->addMinutes($bufferAfter);
                        
                        $isOverlap = false;
                        foreach ($existingBookings as $booking) {
                            if ($booking->starts_at->lt($windowEnd) && $booking->ends_at->gt($windowStart)) {
                                $isOverlap = true;
                                break;
                            }
                        }
                        
                        if (!$isOverlap) {
                            foreach ($blockedDates as $blocked) {
                                if ($blocked->starts_at->lt($windowEnd) && $blocked->ends_at->gt($windowStart)) {
                                    $isOverlap = true;
                                    break;
                                }
                            }
                        }
                        
                        if (!$isOverlap) {
                            $slots[] = [
                                'time' => $slotStart->format('H:i'),
                                'starts_at' => $slotStart->toIso8601String(),
                                'ends_at' => $slotEnd->toIso8601String(),
                                'provider' => null, // Default/virtual host provider
                            ];
                        }
                        $current->addMinutes($duration);
                    }
                }
            }
        } else {
            foreach ($providers as $provider) {
                // Find rules for this provider on this date
                // 1. One-time rules first
                $rules = BookingAvailabilityRule::where('booking_provider_id', $provider->id)
                    ->where('type', 'one-time')
                    ->where('date', $dateStr)
                    ->where('is_enabled', true)
                    ->get();

                // 2. Fall back to recurring rules
                if ($rules->isEmpty()) {
                    $rules = BookingAvailabilityRule::where('booking_provider_id', $provider->id)
                        ->where('type', 'recurring')
                        ->where('weekday', $weekday)
                        ->where('is_enabled', true)
                        ->get();
                }

                if ($rules->isEmpty()) {
                    continue;
                }

                $existingBookings = Booking::where('booking_provider_id', $provider->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('starts_at', $dateStr)
                    ->get();

                $duration = $eventType->duration_minutes;
                $bufferBefore = $eventType->buffer_before ?? 0;
                $bufferAfter = $eventType->buffer_after ?? 0;

                foreach ($rules as $rule) {
                    $start = Carbon::parse($dateStr . ' ' . $rule->start_time);
                    $end = Carbon::parse($dateStr . ' ' . $rule->end_time);
                    $current = $start->copy();

                    while ($current->copy()->addMinutes($duration)->lte($end)) {
                        $slotStart = $current->copy();
                        $slotEnd = $slotStart->copy()->addMinutes($duration);

                        $windowStart = $slotStart->copy()->subMinutes($bufferBefore);
                        $windowEnd = $slotEnd->copy()->addMinutes($bufferAfter);

                        $isOverlap = false;

                        // Check standard overlaps
                        foreach ($existingBookings as $booking) {
                            if ($booking->starts_at->lt($windowEnd) && $booking->ends_at->gt($windowStart)) {
                                $isOverlap = true;
                                break;
                            }
                        }

                        if (!$isOverlap) {
                            foreach ($blockedDates as $blocked) {
                                if ($blocked->starts_at->lt($windowEnd) && $blocked->ends_at->gt($windowStart)) {
                                    $isOverlap = true;
                                    break;
                                }
                            }
                        }

                        if (!$isOverlap) {
                            $slots[] = [
                                'time' => $slotStart->format('H:i'),
                                'starts_at' => $slotStart->toIso8601String(),
                                'ends_at' => $slotEnd->toIso8601String(),
                                'provider' => [
                                    'id' => $provider->id,
                                    'name' => $provider->name,
                                    'specialty' => $provider->specialty,
                                    'avatar_url' => $provider->avatar_url,
                                    'description' => $provider->description,
                                ],
                            ];
                        }
                        $current->addMinutes($duration);
                    }
                }
            }
        }

        // Sort slots chronologically
        usort($slots, function ($a, $b) {
            return strcmp($a['time'], $b['time']);
        });

        return response()->json([
            'slots' => $slots
        ]);
    }

    /**
     * Store a newly created pending booking.
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_type_id' => 'required|exists:booking_event_types,id',
            'booking_provider_id' => 'nullable|exists:booking_providers,id',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'nullable|string|max:255',
            'starts_at' => 'required|date',
            'timezone' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $eventType = BookingEventType::findOrFail($request->event_type_id);
        
        $startsAt = Carbon::parse($request->starts_at);
        $endsAt = $startsAt->copy()->addMinutes($eventType->duration_minutes);

        // Convert guest to client if they don't exist
        $clientUser = User::where('email', $request->guest_email)->first();

        $booking = Booking::create([
            'booking_event_type_id' => $eventType->id,
            'booking_provider_id' => $request->booking_provider_id,
            'client_user_id' => $clientUser ? $clientUser->id : null,
            'guest_name' => $request->guest_name,
            'guest_email' => $request->guest_email,
            'guest_phone' => $request->guest_phone,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'timezone' => $request->timezone,
            'status' => $eventType->requires_payment ? 'pending' : 'confirmed',
            'payment_status' => $eventType->requires_payment ? 'pending' : 'free',
            'price' => $eventType->price,
            'currency' => $eventType->currency,
            'notes' => $request->notes,
        ]);

        if ($eventType->requires_payment && $eventType->price > 0) {
            return redirect()->route('booking.checkout', $booking->id);
        }

        // If free, handle post-booking operations automatically
        $this->handlePostBookingOperations($booking);

        return redirect()->route('booking.success', $booking->id);
    }
    
    /**
     * Show checkout page for the booking.
     */
    public function checkout($id)
    {
        $booking = Booking::with('eventType')->findOrFail($id);
        
        if ($booking->payment_status === 'paid') {
            return redirect()->route('booking.success', $booking->id);
        }

        $user = Auth::user();
        $walletBalance = $user ? (float)$user->user_balance : 0;

        return Inertia::render('Booking/Checkout', [
            'booking' => $booking,
            'walletBalance' => $walletBalance
        ]);
    }

    /**
     * Pay with Balance.
     */
    public function payWithBalance(Request $request, $id)
    {
        $booking = Booking::with('eventType')->findOrFail($id);
        $user = Auth::user();
        $price = $booking->price;

        if (!$user) {
            return back()->withErrors(['error' => 'You must be logged in to pay with wallet.']);
        }

        if ((float) $user->available_balance() < $price) {
            return back()->withErrors(['error' => 'Insufficient balance.']);
        }

        try {
            DB::transaction(function () use ($user, $booking, $price) {
                // Deduct from balance
                $user->add_balance(-1 * $price, 'Booking Payment: ' . $booking->id, 'used');

                $booking->status = 'confirmed';
                $booking->payment_status = 'paid';
                $booking->payment_method = 'balance';
                $booking->transaction_id = 'balance_' . time();
                $booking->save();
                
                $this->handlePostBookingOperations($booking);
            });

            return redirect()->route('booking.success', $booking->id);
        } catch (\Exception $e) {
            Log::error('Booking balance payment failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Payment failed.']);
        }
    }

    /**
     * Pay with Kashier.
     */
    public function payWithKashier(Request $request, $id)
    {
        $booking = Booking::with('eventType')->findOrFail($id);
        
        $paymentUrl = KashierHelper::buildBookingPaymentUrl(
            (float) $booking->price,
            $booking->client_user_id ?? 0,
            $booking->guest_name,
            $booking->guest_email,
            $booking->id,
            $booking->currency ?? 'USD'
        );

        return Inertia::location($paymentUrl);
    }
    
    /**
     * Kashier Webhook for Bookings.
     */
    public function kashierWebhook(Request $request)
    {
        Log::info('Kashier Booking Webhook received:', $request->all());

        if (KashierHelper::validatePayload()) {
            if ($request->input('data.status') === 'SUCCESS') {
                $data = $request->input('data');
                $metadata = $data['metaData'] ?? [];
                if (is_string($metadata)) {
                    $metadata = json_decode($metadata, true) ?: [];
                }

                $bookingId = $metadata['booking_id'] ?? null;
                $source = $metadata['source'] ?? null;
                $trxId = $data['transactionId'] ?? null;

                if ($bookingId && $source === 'booking-purchase' && $trxId) {
                    $booking = Booking::find($bookingId);
                    if ($booking && $booking->payment_status !== 'paid') {
                        $booking->status = 'confirmed';
                        $booking->payment_status = 'paid';
                        $booking->payment_method = 'kashier';
                        $booking->transaction_id = $trxId;
                        $booking->save();
                        
                        $this->handlePostBookingOperations($booking);
                        
                        return response()->json(['status' => 'success']);
                    }
                }
            }
        }
        return response()->json(['status' => 'ignored']);
    }

    public function success($id)
    {
        $booking = Booking::with('eventType')->findOrFail($id);
        return Inertia::render('Booking/Success', ['booking' => $booking]);
    }
    
    /**
     * Host appointments management view.
     */
    public function appointments(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');
        $providerId = $request->input('provider_id');

        $bookings = Booking::with(['eventType', 'clientUser', 'provider'])
            ->whereHas('eventType', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->when($search, function ($q, $search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('guest_name', 'like', "%{$search}%")
                       ->orWhere('guest_email', 'like', "%{$search}%");
                });
            })
            ->when($providerId, function ($q, $providerId) {
                $q->where('booking_provider_id', $providerId);
            })
            ->latest('starts_at')
            ->paginate(20)
            ->withQueryString();
            
        $providers = BookingProvider::where('host_user_id', $user->id)->get();
            
        return Inertia::render('Booking/Appointments', [
            'bookings' => $bookings,
            'providers' => $providers,
            'filters' => $request->only(['search', 'provider_id'])
        ]);
    }

    /**
     * Update booking status (confirm, complete, cancel, etc).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:confirmed,completed,cancelled'
        ]);

        $booking = Booking::findOrFail($id);
        
        if ($booking->eventType->user_id !== Auth::id()) {
            abort(403);
        }

        $booking->status = $request->status;
        $booking->save();

        event(new \Modules\Booking\Events\BookingStatusChanged($booking, $booking->status));

        return back()->with('success', 'Booking status updated successfully.');
    }

    /**
     * Reschedule a booking (Host).
     */
    public function reschedule(Request $request, $id)
    {
        $request->validate([
            'starts_at' => 'required|date'
        ]);

        $booking = Booking::with('eventType')->findOrFail($id);
        
        if ($booking->eventType->user_id !== Auth::id()) {
            abort(403);
        }

        $startsAt = Carbon::parse($request->starts_at);
        $endsAt = $startsAt->copy()->addMinutes($booking->eventType->duration_minutes);

        $booking->starts_at = $startsAt;
        $booking->ends_at = $endsAt;
        $booking->save();

        event(new \Modules\Booking\Events\BookingStatusChanged($booking, $booking->status, true));

        return back()->with('success', 'Booking rescheduled successfully.');
    }

    /**
     * Add notes to booking.
     */
    public function updateNotes(Request $request, $id)
    {
        $request->validate([
            'internal_notes' => 'nullable|string'
        ]);

        $booking = Booking::findOrFail($id);
        
        if ($booking->eventType->user_id !== Auth::id()) {
            abort(403);
        }

        $booking->internal_notes = $request->internal_notes;
        $booking->save();

        return back()->with('success', 'Notes updated successfully.');
    }

    /**
     * Convert to Project (Placeholder for full ERP logic)
     */
    public function createProject(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        
        if ($booking->eventType->user_id !== Auth::id()) {
            abort(403);
        }

        return back()->with('success', 'Project created successfully from this booking.');
    }

    /**
     * Convert to Invoice (Placeholder for full ERP logic)
     */
    public function createInvoice(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        
        if ($booking->eventType->user_id !== Auth::id()) {
            abort(403);
        }

        return back()->with('success', 'Invoice created successfully for this booking.');
    }
    
    /**
     * Handle conversion to client/project.
     */
    private function handlePostBookingOperations(Booking $booking)
    {
        // 1. Client Conversion
        if (!$booking->client_user_id) {
            $user = User::where('email', $booking->guest_email)->first();
            if ($user) {
                $booking->client_user_id = $user->id;
                $booking->save();
            } else {
                $user = User::create([
                    'name' => $booking->guest_name,
                    'email' => $booking->guest_email,
                    'password' => bcrypt(str_random(16)),
                ]);
                $booking->client_user_id = $user->id;
                $booking->save();
            }
        }

        // 2. Add as Tenant Client in ERP if Host has ERP
        $host = $booking->eventType->user;
        $tenant = \Modules\ERP\Models\Tenant::where('user_id', $host->id)->first();
        if ($tenant) {
            $erpClient = \Modules\ERP\Models\TenantClient::firstOrCreate(
                ['tenant_id' => $tenant->id, 'email' => $booking->guest_email],
                [
                    'name' => $booking->guest_name,
                    'phone' => $booking->guest_phone,
                    'currency' => $booking->currency ?? 'USD'
                ]
            );
        }

        // 3. Email notifications via Event
        event(new \Modules\Booking\Events\BookingStatusChanged($booking, 'confirmed'));
    }
}
