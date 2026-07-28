<?php

namespace Modules\WhatsappSender\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Models\TelegramBot;
use Modules\WhatsappSender\Models\WhatsappContactGroup;
use Modules\WhatsappSender\Models\WhatsappContact;
use Modules\WhatsappSender\Models\WhatsappTemplate;
use Modules\WhatsappSender\Services\MetaWhatsappService;
use Modules\WhatsappSender\Services\TelegramBotService;
use Illuminate\Support\Facades\Log;

class SendGroupCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ?WhatsappAccount $account,
        protected WhatsappContactGroup $group,
        protected string $messageType,
        protected ?string $messageBody = null,
        protected ?string $templateName = null,
        protected ?string $templateLanguage = 'en_US',
        protected ?array $templateComponents = null,
        protected ?TelegramBot $bot = null,
        protected string $channel = 'whatsapp'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MetaWhatsappService $whatsappService, TelegramBotService $telegramService): void
    {
        $contacts = $this->group->contacts()->get();
        $business = $this->channel === 'telegram' ? $this->bot?->business : $this->account?->business;

        Log::info("[SendGroupCampaignJob] Starting bulk send to group '{$this->group->name}' ({$contacts->count()} contacts) over channel '{$this->channel}'");

        foreach ($contacts as $contact) {
            // Check balance before sending each message
            if ($business) {
                $business->refresh();
                $fee = (float) $business->per_message_fee;
                if ((float) $business->wallet_balance < $fee) {
                    Log::warning("[SendGroupCampaignJob] Bulk send aborted due to insufficient balance. Business Wallet ID: {$business->id}");
                    break;
                }
            }

            $resolvedComponents = null;
            $bodyText = $this->messageBody ?? '';

            // Resolve dynamic variables mapping for each contact
            if ($this->messageType === 'template' && !empty($this->templateComponents)) {
                $resolvedComponents = [];
                foreach ($this->templateComponents as $comp) {
                    $resolvedComp = ['type' => $comp['type']];
                    if (!empty($comp['parameters'])) {
                        $resolvedParams = [];
                        foreach ($comp['parameters'] as $param) {
                            $resolvedParam = ['type' => $param['type']];
                            $valueKey = $param['value'] ?? '';

                            if ($valueKey === 'name') {
                                $resolvedParam['text'] = $contact->name ?? '';
                            } elseif ($valueKey === 'phone') {
                                $resolvedParam['text'] = $contact->phone;
                            } elseif (str_starts_with($valueKey, 'custom_fields.')) {
                                $field = substr($valueKey, 14);
                                $resolvedParam['text'] = $contact->custom_fields[$field] ?? '';
                            } else {
                                $resolvedParam['text'] = $valueKey;
                            }
                            $resolvedParams[] = $resolvedParam;
                        }
                        $resolvedComp['parameters'] = $resolvedParams;
                    }
                    $resolvedComponents[] = $resolvedComp;
                }
            }

            if ($this->channel === 'telegram') {
                // If template is used for Telegram, reconstruct it locally as text
                if ($this->messageType === 'template' && $business) {
                    $template = WhatsappTemplate::where('whatsapp_business_id', $business->id)
                        ->where('name', $this->templateName)
                        ->first();
                    if ($template) {
                        $bodyComponent = collect($template->components)->firstWhere('type', 'BODY');
                        $bodyText = $bodyComponent['text'] ?? '';

                        if ($resolvedComponents) {
                            $bodyParams = collect($resolvedComponents)->firstWhere('type', 'body')['parameters'] ?? [];
                            foreach ($bodyParams as $index => $param) {
                                $bodyText = str_replace('{{' . ($index + 1) . '}}', $param['text'] ?? '', $bodyText);
                            }
                        }
                    }
                }

                // Send Telegram message (the recipient phone stores the Chat ID)
                $telegramService->sendMessage(
                    $this->bot,
                    $contact->phone,
                    $bodyText,
                    $this->messageType
                );
            } else {
                // Send WhatsApp message
                $templateData = null;
                if ($this->messageType === 'template') {
                    $templateData = [
                        'name' => $this->templateName,
                        'language' => $this->templateLanguage,
                        'components' => $resolvedComponents,
                    ];
                }

                $whatsappService->sendMessage(
                    $this->account,
                    $contact->phone,
                    $bodyText,
                    $this->messageType,
                    $templateData
                );
            }
        }

        Log::info("[SendGroupCampaignJob] Completed bulk send to group '{$this->group->name}' over channel '{$this->channel}'");
    }
}
