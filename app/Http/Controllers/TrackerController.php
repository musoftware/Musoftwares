<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackerController extends Controller
{
    // A standard 1x1 transparent GIF
    private const PIXEL_GIF = "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3b";

    public function pixel(Request $request, $payload)
    {
        try {
            $data = json_decode(base64_decode($payload), true);
            
            if (isset($data['u']) && isset($data['c']) && isset($data['t'])) {
                DB::table('email_tracking_events')->insert([
                    'user_id'     => $data['u'],
                    'campaign_id' => $data['c'],
                    'contact_id'  => $data['t'],
                    'ip'          => $request->ip(),
                    'user_agent'  => substr($request->userAgent(), 0, 255),
                    'opened_at'   => now(),
                    'created_at'  => now(),
                    'updated_at'  => now()
                ]);
            }
        } catch (\Exception $e) {
            // Ignore errors, we must always return the pixel
        }

        return response(self::PIXEL_GIF, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function click(Request $request, $payload)
    {
        try {
            $data = json_decode(base64_decode($payload), true);
            $url = $request->query('url');

            if (isset($data['u']) && isset($data['c']) && isset($data['t']) && $url) {
                DB::table('email_tracking_events')->insert([
                    'user_id'     => $data['u'],
                    'campaign_id' => $data['c'],
                    'contact_id'  => $data['t'],
                    'ip'          => $request->ip(),
                    'user_agent'  => substr($request->userAgent(), 0, 255),
                    'event_type'  => 'click',
                    'url'         => $url,
                    'opened_at'   => now(),
                    'created_at'  => now(),
                    'updated_at'  => now()
                ]);
            }
        } catch (\Exception $e) {
            // Ignore errors, redirect anyway
        }

        return redirect($url ?? '/');
    }

    public function unsubscribe(Request $request, $payload)
    {
        try {
            $data = json_decode(base64_decode($payload), true);
            
            if (isset($data['u']) && isset($data['c']) && isset($data['t'])) {
                DB::table('email_tracking_events')->insert([
                    'user_id'     => $data['u'],
                    'campaign_id' => $data['c'],
                    'contact_id'  => $data['t'],
                    'ip'          => $request->ip(),
                    'user_agent'  => substr($request->userAgent(), 0, 255),
                    'event_type'  => 'unsubscribe',
                    'opened_at'   => now(),
                    'created_at'  => now(),
                    'updated_at'  => now()
                ]);
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return response(
            '<html><head><title>Unsubscribed</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;background:#f9fafb;color:#374151;text-align:center;} h2{color:#111827;}</style></head><body><div><h2>You have been unsubscribed</h2><p>You will no longer receive emails from this sender.</p></div></body></html>',
            200
        )->header('Content-Type', 'text/html');
    }

    public function sync(Request $request)
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json(['error' => 'Missing token'], 401);
        }
        
        $userId = trim(str_replace('Bearer ', '', $token));
        
        // Fast sync: read chunk and delete atomically
        $events = DB::transaction(function () use ($userId) {
            $records = DB::table('email_tracking_events')
                ->where('user_id', $userId)
                ->limit(1000)
                ->get();
            
            if ($records->isNotEmpty()) {
                $ids = $records->pluck('id')->toArray();
                DB::table('email_tracking_events')->whereIn('id', $ids)->delete();
            }
            
            return $records;
        });

        return response()->json(['events' => $events]);
    }
}
