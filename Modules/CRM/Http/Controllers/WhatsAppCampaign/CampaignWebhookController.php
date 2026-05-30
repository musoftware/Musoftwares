<?php

namespace Modules\CRM\Http\Controllers\WhatsAppCampaign;

use App\Http\Controllers\Controller;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Jobs\ProcessCampaignWebhookJob;
use Modules\CRM\Models\WhatsAppAccount;
use Illuminate\Http\Request;

class CampaignWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('X-WhatsApp-Signature');
        $payload = $request->all();
        $accountId = $payload['account_id'] ?? null;

        if (!$accountId) {
            return response()->json(['error' => 'Missing account_id'], 400);
        }

        $account = WhatsAppAccount::withoutGlobalScopes()->find($accountId);
        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        // Dispatch to queue for processing
        ProcessCampaignWebhookJob::dispatch($account, $payload);

        return response()->json(['status' => 'accepted'], 202);
    }
}
