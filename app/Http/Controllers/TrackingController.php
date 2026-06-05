<?php

namespace App\Http\Controllers;

use App\Models\NotificationCampaign;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * Track a click on a notification campaign and redirect to the target URL.
     */
    public function trackCampaign(Request $request, $id)
    {
        $campaign = NotificationCampaign::findOrFail($id);

        // Increment the clicks count
        $campaign->increment('clicks_count');

        $redirectUrl = $request->query('redirect');

        if ($redirectUrl) {
            return redirect()->away($redirectUrl);
        }

        // Fallback if no redirect is specified, redirect to home
        return redirect()->to('/');
    }
}
