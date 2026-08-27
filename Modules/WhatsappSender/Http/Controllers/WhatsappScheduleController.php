<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Modules\WhatsappSender\Models\WhatsappSchedule;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class WhatsappScheduleController extends Controller
{
    /**
     * Schedule a message (individual or bulk campaign).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'whatsapp_business_id' => ['required', 'exists:whatsapp_businesses,id'],
            'whatsapp_account_id' => ['required_without:telegram_bot_id', 'nullable', 'exists:whatsapp_accounts,id'],
            'telegram_bot_id' => ['required_without:whatsapp_account_id', 'nullable', 'exists:telegram_bots,id'],
            'channel' => ['nullable', 'string', 'in:whatsapp,telegram'],
            'whatsapp_contact_group_id' => ['nullable', 'exists:whatsapp_contact_groups,id'],
            'recipient_phone' => ['nullable', 'string'],
            'message_type' => ['required', 'string', 'in:text,template'],
            'message_body' => ['nullable', 'required_unless:message_type,template', 'string', 'max:4096'],
            'template_name' => ['required_if:message_type,template', 'nullable', 'string', 'max:255'],
            'template_language' => ['nullable', 'string', 'max:10'],
            'template_components' => ['nullable', 'array'],
            'scheduled_at' => ['required', 'string'], // Expected format: YYYY-MM-DD HH:MM
        ]);

        $channel = $validated['channel'] ?? 'whatsapp';

        if (empty($validated['whatsapp_contact_group_id']) && empty($validated['recipient_phone'])) {
            throw ValidationException::withMessages([
                'recipient_phone' => 'You must either select a Contact Group or enter a single Recipient.',
            ]);
        }

        // Validate scheduled time in Cairo timezone
        try {
            $cairoTime = Carbon::parse($validated['scheduled_at'], 'Africa/Cairo');
            $nowCairo = Carbon::now('Africa/Cairo');

            if ($cairoTime->lte($nowCairo)) {
                throw ValidationException::withMessages([
                    'scheduled_at' => 'The scheduled time must be in the future (Cairo Time: ' . $nowCairo->format('Y-m-d H:i') . ').',
                ]);
            }

            // Convert to system UTC timezone for DB storage
            $scheduledAtUtc = $cairoTime->utc();
        } catch (\Throwable $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }
            throw ValidationException::withMessages([
                'scheduled_at' => 'Invalid date/time format. Please use YYYY-MM-DD HH:MM.',
            ]);
        }

        // Check ownership of business
        $businessQuery = WhatsappBusiness::where('id', $validated['whatsapp_business_id']);
        if (!$user->isAdmin()) {
            $businessQuery->where('user_id', $user->id);
        }
        $business = $businessQuery->firstOrFail();

        $accountId = null;
        $botId = null;

        if ($channel === 'telegram') {
            $bot = \Modules\WhatsappSender\Models\TelegramBot::where('whatsapp_business_id', $business->id)
                ->where('id', $validated['telegram_bot_id'])
                ->firstOrFail();
            $botId = $bot->id;
        } else {
            $account = WhatsappAccount::where('whatsapp_business_id', $business->id)
                ->where('id', $validated['whatsapp_account_id'])
                ->firstOrFail();
            $accountId = $account->id;
        }

        $recipient = $validated['recipient_phone'] ?? '';
        if ($channel === 'whatsapp' && !empty($recipient)) {
            $recipient = preg_replace('/[^0-9]/', '', $recipient);
        }

        WhatsappSchedule::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'whatsapp_account_id' => $accountId,
            'telegram_bot_id' => $botId,
            'whatsapp_contact_group_id' => $validated['whatsapp_contact_group_id'] ?? null,
            'recipient_phone' => $recipient,
            'channel' => $channel,
            'message_type' => $validated['message_type'],
            'message_body' => $validated['message_body'] ?? null,
            'template_name' => $validated['template_name'] ?? null,
            'template_language' => $validated['template_language'] ?? 'en_US',
            'template_components' => $validated['template_components'] ?? null,
            'scheduled_at' => $scheduledAtUtc,
            'status' => 'pending',
        ]);

        return redirect()->route('whatsapp.businesses.workspace', $business->id)
            ->with('success', 'Message scheduled successfully (set for ' . $cairoTime->format('Y-m-d H:i') . ' Cairo Time).');
    }

    /**
     * Cancel/delete a scheduled message.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $query = WhatsappSchedule::where('id', $id);
        if (!$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('business', function ($bq) use ($user) {
                      $bq->where('user_id', $user->id);
                  });
            });
        }
        $schedule = $query->firstOrFail();

        $businessId = $schedule->whatsapp_business_id;

        if ($schedule->status !== 'pending') {
            return redirect()->route('whatsapp.businesses.workspace', $businessId)->with('error', 'Only pending schedules can be cancelled.');
        }

        $schedule->delete();

        return redirect()->route('whatsapp.businesses.workspace', $businessId)->with('success', 'Scheduled campaign cancelled and deleted successfully.');
    }
}
