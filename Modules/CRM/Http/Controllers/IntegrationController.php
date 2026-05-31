<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IntegrationController extends Controller
{
    public function index()
    {
        return Inertia::render('CRM/Integrations/Index', [
            'api_token' => auth()->user()->createToken('crm-api')->plainTextToken, // Just for MVP display
            'webhook_url' => route('api.crm.webhook.receive'),
        ]);
    }
}
