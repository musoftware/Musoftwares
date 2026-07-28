<?php

namespace Modules\WhatsappSender\Console;

use Illuminate\Console\Command;
use Modules\WhatsappSender\Models\WhatsappSchedule;
use Modules\WhatsappSender\Models\WhatsappTemplate;
use Modules\WhatsappSender\Services\MetaWhatsappService;
use Modules\WhatsappSender\Services\TelegramBotService;
use Modules\WhatsappSender\Jobs\SendGroupCampaignJob;
use Illuminate\Support\Facades\Log;

class ProcessScheduledMessagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'whatsapp:process-scheduled';

    /**
     * The console command description.
     */
    protected $description = 'Process pending scheduled WhatsApp & Telegram campaigns and messages.';

    public function __construct(
        protected MetaWhatsappService $whatsappService,
        protected TelegramBotService $telegramService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $now = now();
        $schedules = WhatsappSchedule::where('status', 'pending')
            ->where('scheduled_at', '<=', $now)
            ->get();

        if ($schedules->isEmpty()) {
            return;
        }

        $this->info("Processing {$schedules->count()} pending scheduled messages.");

        foreach ($schedules as $schedule) {
            $schedule->update(['status' => 'processing']);

            try {
                if ($schedule->channel === 'telegram') {
                    $bot = $schedule->telegramBot;
                    if (!$bot || $bot->status !== 'active') {
                        throw new \Exception('No active Telegram Bot connected found for schedule.');
                    }

                    if (!empty($schedule->whatsapp_contact_group_id)) {
                        $group = $schedule->group;
                        if (!$group) {
                            throw new \Exception('Contact group not found.');
                        }

                        // Dispatch queued bulk group campaign job
                        SendGroupCampaignJob::dispatch(
                            null,
                            $group,
                            $schedule->message_type,
                            $schedule->message_body,
                            $schedule->template_name,
                            $schedule->template_language,
                            $schedule->template_components,
                            $bot,
                            'telegram'
                        );

                        $schedule->update([
                            'status' => 'sent',
                        ]);
                    } else {
                        // Send individual scheduled message immediately
                        $bodyText = $schedule->message_body ?? '';
                        if ($schedule->message_type === 'template') {
                            $template = WhatsappTemplate::where('whatsapp_business_id', $schedule->whatsapp_business_id)
                                ->where('name', $schedule->template_name)
                                ->first();
                            if ($template) {
                                $bodyComponent = collect($template->components)->firstWhere('type', 'BODY');
                                $bodyText = $bodyComponent['text'] ?? '';
                                
                                if (!empty($schedule->template_components)) {
                                    $bodyParams = collect($schedule->template_components)->firstWhere('type', 'body')['parameters'] ?? [];
                                    foreach ($bodyParams as $index => $param) {
                                        $bodyText = str_replace('{{' . ($index + 1) . '}}', $param['text'] ?? '', $bodyText);
                                    }
                                }
                            }
                        }

                        $result = $this->telegramService->sendMessage(
                            $bot,
                            $schedule->recipient_phone,
                            $bodyText,
                            $schedule->message_type
                        );

                        if ($result['success']) {
                            $schedule->update([
                                'status' => 'sent',
                            ]);
                        } else {
                            $schedule->update([
                                'status' => 'failed',
                                'error_message' => $result['error'] ?? 'Telegram API error',
                            ]);
                        }
                    }
                } else {
                    // WhatsApp Channel
                    $account = $schedule->account;
                    if (!$account || $account->status !== 'active') {
                        throw new \Exception('No active WhatsApp connected account found for schedule.');
                    }

                    if (!empty($schedule->whatsapp_contact_group_id)) {
                        $group = $schedule->group;
                        if (!$group) {
                            throw new \Exception('Contact group not found.');
                        }

                        // Dispatch queued bulk group campaign job
                        SendGroupCampaignJob::dispatch(
                            $account,
                            $group,
                            $schedule->message_type,
                            $schedule->message_body,
                            $schedule->template_name,
                            $schedule->template_language,
                            $schedule->template_components,
                            null,
                            'whatsapp'
                        );

                        $schedule->update([
                            'status' => 'sent',
                        ]);
                    } else {
                        // Send individual scheduled message immediately
                        $templateData = null;
                        if ($schedule->message_type === 'template') {
                            $templateData = [
                                'name' => $schedule->template_name,
                                'language' => $schedule->template_language,
                                'components' => $schedule->template_components,
                            ];
                        }

                        $result = $this->whatsappService->sendMessage(
                            $account,
                            $schedule->recipient_phone,
                            $schedule->message_body ?? '',
                            $schedule->message_type,
                            $templateData
                        );

                        if ($result['success']) {
                            $schedule->update([
                                'status' => 'sent',
                            ]);
                        } else {
                            $schedule->update([
                                'status' => 'failed',
                                'error_message' => $result['error'] ?? 'Meta API error',
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error("[ProcessScheduledMessagesCommand] Schedule ID {$schedule->id} failed: " . $e->getMessage());
                $schedule->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Scheduled messages processed successfully.");
    }
}
