<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingEventType;
use App\Models\User;
use Modules\Core\Models\WalletTransaction;
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
        $bookings = Booking::with(['eventType', 'clientUser'])
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
        $eventType = BookingEventType::where('user_id', $host->id)->where('slug', $slug)->firstOrFail();

        return Inertia::render('Booking/Public/Show', [
            'host' => $host->only('id', 'name', 'avatar'),
            'eventType' => $eventType
        ]);
    }

    /**
     * Store a newly created pending booking.
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_type_id' => 'required|exists:booking_event_types,id',
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

        // If free, handle conversion to client automatically
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

        $user = Auth::user(); // Client might be logged in
        $walletBalance = $user ? ($user->getWallet()->balance ?? 0) : 0;

        return Inertia::render('Booking/Checkout', [
            'booking' => $booking,
            'walletBalance' => $walletBalance
        ]);
    }

    /**
     * Pay with Wallet.
     */
    public function payWithWallet(Request $request, $id)
    {
        $booking = Booking::with('eventType')->findOrFail($id);
        $user = Auth::user();

        if (!$user) {
            return back()->withErrors(['error' => 'You must be logged in to pay with wallet.']);
        }

        $wallet = $user->getWallet();
        $price = $booking->price;

        if ($wallet->balance < $price) {
            return back()->withErrors(['error' => 'Insufficient wallet balance.']);
        }

        try {
            DB::transaction(function () use ($user, $wallet, $booking, $price) {
                // Deduct from wallet
                $balanceBefore = $wallet->balance;
                $balanceAfter = $balanceBefore - $price;

                $wallet->balance = $balanceAfter;
                $wallet->save();

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'debit',
                    'amount' => $price,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reference_type' => 'booking_payment',
                    'description' => "Booking payment for {$booking->eventType->title}",
                ]);

                // Update booking
                $booking->status = 'confirmed';
                $booking->payment_status = 'paid';
                $booking->payment_method = 'wallet';
                $booking->transaction_id = 'wallet_' . time();
                $booking->save();
                
                $this->handlePostBookingOperations($booking);
            });

            return redirect()->route('booking.success', $booking->id);
        } catch (\Exception $e) {
            Log::error('Booking wallet payment failed: ' . $e->getMessage());
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
    public function appointments()
    {
        $user = Auth::user();
        $bookings = Booking::with(['eventType', 'clientUser'])
            ->whereHas('eventType', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest('starts_at')
            ->paginate(20);
            
        return Inertia::render('Booking/Appointments', [
            'bookings' => $bookings
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
        
        // Ensure user owns this booking's event type
        if ($booking->eventType->user_id !== Auth::id()) {
            abort(403);
        }

        $booking->status = $request->status;
        $booking->save();

        return back()->with('success', 'Booking status updated successfully.');
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

        // Placeholder for real logic
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

        // Placeholder for real logic
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

        // 3. Email notifications
        \Illuminate\Support\Facades\Mail::to($booking->guest_email)
            ->send(new \App\Mail\BookingConfirmed($booking));
    }
}
