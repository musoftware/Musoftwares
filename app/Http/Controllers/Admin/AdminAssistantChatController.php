<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiAssistant\AssistantLog;
use Inertia\Inertia;
use Inertia\Response;

/** The owner reads what visitors ask the site's AI assistant. */
class AdminAssistantChatController extends Controller
{
    public function index(AssistantLog $log): Response
    {
        return Inertia::render('Admin/Assistant/Index', ['visitors' => $log->visitors()]);
    }

    public function show(AssistantLog $log, string $visitor): Response
    {
        $conversation = $log->conversation($visitor);
        abort_if($conversation->isEmpty(), 404);

        return Inertia::render('Admin/Assistant/Show', ['visitor' => $visitor, 'exchanges' => $conversation->values()]);
    }
}
