<?php

namespace Modules\CRM\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CRM\Models\Lead;

class WebhookReceiveController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        // Very simple logic to create a lead from Zapier/Make webhook
        if (isset($payload['email']) || isset($payload['phone'])) {
            Lead::create([
                'name' => $payload['name'] ?? 'Unknown Lead',
                'email' => $payload['email'] ?? null,
                'phone' => $payload['phone'] ?? null,
                'source' => 'api',
                'custom_data' => $payload,
            ]);

            return response()->json(['status' => 'success', 'message' => 'Lead created']);
        }

        return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
    }
}
