<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AI\WhatsappChatAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AdminWhatsappAiPlaygroundController extends Controller
{
    public function __construct(
        protected WhatsappChatAiService $aiService
    ) {}

    public function index(Request $request)
    {
        $clients = User::orderBy('name')->get(['id', 'name', 'email']);
        
        $selectedClientId = $request->get('client_id');
        $selectedClient = $selectedClientId ? User::find($selectedClientId) : null;
        
        $chatHistory = [];
        if ($selectedClient) {
            $chatHistory = Cache::get('whatsapp_ai_playground_history_' . $selectedClient->id, []);
        }

        return Inertia::render('Admin/Whatsapp/AiPlayground', [
            'clients'        => $clients,
            'selectedClient' => $selectedClient,
            'chatHistory'    => $chatHistory,
        ]);
    }

    public function chat(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:users,id',
            'message'   => 'required|string',
        ]);

        $client = User::find($request->client_id);
        $message = $request->message;

        $historyKey = 'whatsapp_ai_playground_history_' . $client->id;
        $history = Cache::get($historyKey, []);

        try {
            $response = $this->aiService->generateContextualResponse($message, $client, $history);

            $history[] = ['role' => 'user', 'content' => $message];
            $history[] = ['role' => 'assistant', 'content' => $response];
            
            $history = array_slice($history, -20);
            Cache::put($historyKey, $history, now()->addHours(2));

            return response()->json([
                'success'  => true,
                'response' => $response,
                'history'  => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function clearHistory(Request $request)
    {
        $request->validate(['client_id' => 'required|exists:users,id']);
        Cache::forget('whatsapp_ai_playground_history_' . $request->client_id);
        
        return back()->with('success', __('ui.admin.whatsapp_ai_playground.history_cleared'));
    }
}
