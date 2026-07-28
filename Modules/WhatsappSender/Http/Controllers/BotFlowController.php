<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\WhatsappSender\Models\BotFlow;

class BotFlowController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'whatsapp_business_id' => ['required', 'exists:whatsapp_businesses,id'],
            'channel' => ['required', 'string', 'in:whatsapp,telegram'],
            'telegram_bot_id' => ['nullable', 'exists:telegram_bots,id'],
            'name' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', 'string', 'in:keyword,start_bot,default'],
            'trigger_keywords' => ['nullable', 'array'],
            'nodes' => ['nullable', 'array'],
            'edges' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        BotFlow::create($validated);

        return back()->with('success', 'Chat Flow created successfully!');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $flow = BotFlow::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', 'string', 'in:keyword,start_bot,default'],
            'trigger_keywords' => ['nullable', 'array'],
            'nodes' => ['nullable', 'array'],
            'edges' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $flow->update($validated);

        return back()->with('success', 'Chat Flow saved successfully!');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $flow = BotFlow::findOrFail($id);
        $flow->update(['is_active' => !$flow->is_active]);

        return back()->with('success', 'Chat Flow status toggled!');
    }

    public function destroy(int $id): RedirectResponse
    {
        $flow = BotFlow::findOrFail($id);
        $flow->delete();

        return back()->with('success', 'Chat Flow deleted successfully!');
    }
}
