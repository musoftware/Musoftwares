<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Booking\Models\BookingEventType;
use Modules\Booking\Models\BookingProvider;
use Modules\Booking\Models\BookingAvailabilityRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingProviderController extends Controller
{
    /**
     * Display a listing of providers for the current host.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $providers = BookingProvider::where('host_user_id', $user->id)
            ->with(['eventTypes', 'availabilityRules'])
            ->get();
            
        $eventTypes = BookingEventType::where('user_id', $user->id)->get();

        return Inertia::render('Booking/Providers', [
            'providers' => $providers,
            'eventTypes' => $eventTypes
        ]);
    }

    /**
     * Store a newly created provider.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'event_type_ids' => 'nullable|array',
            'event_type_ids.*' => 'exists:booking_event_types,id',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $provider = BookingProvider::create([
                    'host_user_id' => Auth::id(),
                    'name' => $validated['name'],
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'specialty' => $validated['specialty'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'is_active' => $validated['is_active'] ?? true,
                ]);

                if (!empty($validated['event_type_ids'])) {
                    $provider->eventTypes()->sync($validated['event_type_ids']);
                }
                
                // Set default availability rule: 9:00 - 17:00 on weekdays (Mon-Fri)
                for ($day = 1; $day <= 5; $day++) {
                    BookingAvailabilityRule::create([
                        'booking_provider_id' => $provider->id,
                        'type' => 'recurring',
                        'weekday' => $day,
                        'start_time' => '09:00:00',
                        'end_time' => '17:00:00',
                        'is_enabled' => true,
                    ]);
                }
            });

            return redirect()->route('booking.providers.index')->with('success', __('general.provider_registered_successfully_with_default_weekday_schedules'));
        } catch (\Exception $e) {
            Log::error('Failed to register provider: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create provider.']);
        }
    }

    /**
     * Update the specified provider.
     */
    public function update(Request $request, $id)
    {
        $provider = BookingProvider::where('host_user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'event_type_ids' => 'nullable|array',
            'event_type_ids.*' => 'exists:booking_event_types,id',
        ]);

        try {
            DB::transaction(function () use ($provider, $validated) {
                $provider->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'specialty' => $validated['specialty'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'is_active' => $validated['is_active'] ?? true,
                ]);

                $provider->eventTypes()->sync($validated['event_type_ids'] ?? []);
            });

            return redirect()->route('booking.providers.index')->with('success', __('general.provider_profile_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update provider: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update provider profile.']);
        }
    }

    /**
     * Save/overwrite schedules (both weekly shifts & custom days).
     */
    public function saveAvailability(Request $request, $id)
    {
        $provider = BookingProvider::where('host_user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'rules' => 'required|array',
            'rules.*.type' => 'required|in:recurring,one-time',
            'rules.*.weekday' => 'nullable|integer|between:0,6',
            'rules.*.date' => 'nullable|date',
            'rules.*.start_time' => 'required|string',
            'rules.*.end_time' => 'required|string',
            'rules.*.is_enabled' => 'boolean',
        ]);

        try {
            DB::transaction(function () use ($provider, $request) {
                // Remove existing rules
                BookingAvailabilityRule::where('booking_provider_id', $provider->id)->delete();

                // Create new rules
                foreach ($request->rules as $rule) {
                    BookingAvailabilityRule::create([
                        'booking_provider_id' => $provider->id,
                        'type' => $rule['type'],
                        'weekday' => $rule['type'] === 'recurring' ? $rule['weekday'] : null,
                        'date' => $rule['type'] === 'one-time' ? $rule['date'] : null,
                        'start_time' => $rule['start_time'],
                        'end_time' => $rule['end_time'],
                        'is_enabled' => $rule['is_enabled'] ?? true,
                    ]);
                }
            });

            return redirect()->route('booking.providers.index')->with('success', __('general.provider_availability_schedule_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to save schedules: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update availability schedule.']);
        }
    }
}
