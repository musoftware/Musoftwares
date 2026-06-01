<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Models\BookingBlockedDate;
use Modules\Booking\Models\BookingProvider;

class BookingExceptionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $providers = BookingProvider::where('host_user_id', $user->id)->get();
        $providerIds = $providers->pluck('id');
        
        $exceptions = BookingBlockedDate::with('provider')
            ->whereIn('booking_provider_id', $providerIds)
            ->orderBy('starts_at', 'asc')
            ->get();
            
        return Inertia::render('Booking/Exceptions', [
            'providers' => $providers,
            'exceptions' => $exceptions,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'booking_provider_id' => 'required|exists:booking_providers,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'reason' => 'nullable|string|max:255',
            'is_recurring' => 'boolean',
            'recurring_pattern' => 'nullable|string',
        ]);
        
        // Ensure user owns this provider
        $provider = BookingProvider::where('host_user_id', Auth::id())->findOrFail($request->booking_provider_id);
        
        BookingBlockedDate::create([
            'booking_provider_id' => $provider->id,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'reason' => $request->reason,
            'is_recurring' => $request->is_recurring ?? false,
            'recurring_pattern' => $request->recurring_pattern,
        ]);
        
        return back()->with('success', __('general.exception_added_successfully'));
    }
    
    public function destroy($id)
    {
        $exception = BookingBlockedDate::findOrFail($id);
        
        // Ensure user owns this provider
        BookingProvider::where('host_user_id', Auth::id())->findOrFail($exception->booking_provider_id);
        
        $exception->delete();
        
        return back()->with('success', __('general.exception_removed_successfully'));
    }
}
