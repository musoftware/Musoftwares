<?php

namespace App\Http\Controllers;

use App\Models\NotificationCampaign;
use App\Models\NotificationCampaignView;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * Track a click on a notification campaign and redirect to the target URL.
     */
    public function trackCampaign(Request $request, $id)
    {
        $campaign = NotificationCampaign::findOrFail($id);

        // Increment the global clicks count
        $campaign->increment('clicks_count');

        // Log personal click if user_id is provided
        if ($request->has('user_id')) {
            NotificationCampaignView::firstOrCreate([
                'notification_campaign_id' => $campaign->id,
                'user_id' => $request->query('user_id'),
                'type' => 'click',
            ]);
        }

        $redirectUrl = $request->query('redirect');

        if ($redirectUrl) {
            return redirect()->away($redirectUrl);
        }

        // Fallback if no redirect is specified, redirect to home
        return redirect()->to('/');
    }

    /**
     * Track a view on a notification campaign and return a tracking pixel/logo.
     */
    public function trackCampaignView(Request $request, $id)
    {
        $campaign = NotificationCampaign::find($id);

        if ($campaign) {
            // Increment the global views count
            $campaign->increment('views_count');

            // Log personal view if user_id is provided
            if ($request->has('user_id')) {
                NotificationCampaignView::firstOrCreate([
                    'notification_campaign_id' => $campaign->id,
                    'user_id' => $request->query('user_id'),
                    'type' => 'view',
                ]);
            }
        }

        $imagePath = public_path('icons/pwa-192.png');

        if (! file_exists($imagePath)) {
            // Fallback to a transparent 1x1 pixel if the logo doesn't exist
            $transparentPixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

            return response($transparentPixel, 200)
                ->header('Content-Type', 'image/gif')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        return response()->file($imagePath, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
