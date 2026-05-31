<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\CRM\Models\WhatsAppAccount;

class SettingController extends Controller
{
    public function index()
    {
        $workspaceId = session('crm_workspace_id');
        
        // Load WhatsApp accounts for this workspace to pass to settings
        $whatsappAccounts = [];
        if (feature('crm.wa_inbox') || feature('crm.whatsapp_campaigns') || feature('crm-advanced-operations')) {
            $whatsappAccounts = WhatsAppAccount::where('workspace_id', $workspaceId)->get();
        }

        return Inertia::render('CRM/Settings/Index', [
            'whatsapp_accounts' => $whatsappAccounts,
            'api_token' => auth()->user()->createToken('crm-api')->plainTextToken,
            'webhook_url' => route('api.crm.webhook.receive'),
        ]);
    }

    public function store(Request $request)
    {
        // Handle generic CRM settings save here
        return redirect()->back()->with('success', __('crm.settings_saved_successfully'));
    }
}
