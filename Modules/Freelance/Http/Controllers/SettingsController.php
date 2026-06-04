<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Freelance\Models\FreelanceProfile;
use Carbon\Carbon;

class SettingsController extends Controller
{
    public function notifications(Request $request)
    {
        $user = $request->user();
        
        $profile = FreelanceProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'title' => '',
                'bio' => '',
                'hourly_rate' => 0,
                'receive_job_notifications' => true,
            ]
        );

        return Inertia::render('Freelance/Settings/Notifications', [
            'profile' => $profile,
        ]);
    }

    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'receive_job_notifications' => 'required|boolean',
            'mute_duration' => 'nullable|string|in:1_hour,24_hours,1_week,forever',
        ]);

        $user = $request->user();
        $profile = FreelanceProfile::firstOrCreate(['user_id' => $user->id]);

        $profile->receive_job_notifications = $validated['receive_job_notifications'];

        if (isset($validated['mute_duration']) && $validated['mute_duration']) {
            switch ($validated['mute_duration']) {
                case '1_hour':
                    $profile->notifications_muted_until = now()->addHour();
                    break;
                case '24_hours':
                    $profile->notifications_muted_until = now()->addHours(24);
                    break;
                case '1_week':
                    $profile->notifications_muted_until = now()->addWeeks(1);
                    break;
                case 'forever':
                    $profile->notifications_muted_until = Carbon::create(2099, 12, 31);
                    break;
            }
        } else if ($validated['receive_job_notifications'] === true && !isset($validated['mute_duration'])) {
            // If they are turning notifications ON without a mute duration, clear the mute
            $profile->notifications_muted_until = null;
        }

        $profile->save();

        return redirect()->back()->with('success', __('general.saved_successfully'));
    }
}
