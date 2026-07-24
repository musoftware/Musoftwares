<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IncomingWebhook;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncomingWebhooksController extends Controller
{
    public function index(Request $request): Response
    {
        $webhooks = IncomingWebhook::query()
            ->orderBy('id', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/Settings/IncomingWebhooks/Index', [
            'webhooks' => $webhooks,
        ]);
    }

    public function show(IncomingWebhook $incomingWebhook): Response
    {
        return Inertia::render('Admin/Settings/IncomingWebhooks/Show', [
            'webhook' => $incomingWebhook,
        ]);
    }
}
