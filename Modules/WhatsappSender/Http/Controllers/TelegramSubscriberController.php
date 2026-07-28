<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\WhatsappSender\Models\TelegramSubscriber;
use Modules\WhatsappSender\Models\TelegramSubscriberGroup;

class TelegramSubscriberController extends Controller
{
    public function storeGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'telegram_bot_id' => ['required', 'exists:telegram_bots,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        TelegramSubscriberGroup::create($validated);

        return back()->with('success', 'Subscriber group created successfully!');
    }

    public function destroyGroup(int $id): RedirectResponse
    {
        $group = TelegramSubscriberGroup::findOrFail($id);
        $group->delete();

        return back()->with('success', 'Subscriber group deleted successfully!');
    }

    public function storeSubscriber(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'telegram_bot_id' => ['required', 'exists:telegram_bots,id'],
            'telegram_subscriber_group_id' => ['nullable', 'exists:telegram_subscriber_groups,id'],
            'chat_id' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
        ]);

        TelegramSubscriber::updateOrCreate(
            [
                'telegram_bot_id' => $validated['telegram_bot_id'],
                'chat_id' => $validated['chat_id'],
            ],
            $validated
        );

        return back()->with('success', 'Subscriber saved successfully!');
    }

    public function updateSubscriberGroup(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'telegram_subscriber_group_id' => ['nullable', 'exists:telegram_subscriber_groups,id'],
        ]);

        $subscriber = TelegramSubscriber::findOrFail($id);
        $subscriber->update($validated);

        return back()->with('success', 'Subscriber group updated successfully!');
    }

    public function destroySubscriber(int $id): RedirectResponse
    {
        $subscriber = TelegramSubscriber::findOrFail($id);
        $subscriber->delete();

        return back()->with('success', 'Subscriber deleted successfully!');
    }
}
