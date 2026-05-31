<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $workspaceId = session('crm_workspace_id');
        
        return Inertia::render('CRM/Settings/Index', [
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
