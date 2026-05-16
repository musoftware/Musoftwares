<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\ERP\Models\TimerSession;

class TimerController extends Controller
{
    public function show($id)
    {
        $timer = TimerSession::find($id);

        if (!$timer) {
            return response()->json(['duration_seconds' => 0, 'stopped_at' => now()]);
        }

        return response()->json($timer);
    }
}
