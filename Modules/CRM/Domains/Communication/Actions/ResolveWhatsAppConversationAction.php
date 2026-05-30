<?php

namespace Modules\CRM\Domains\Communication\Actions;

use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\WhatsAppSlaEngine;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\ConversationRoutingEngine;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class ResolveWhatsAppConversationAction
{
    public function __construct(
        protected WhatsAppSlaEngine $slaEngine,
        protected ConversationRoutingEngine $routingEngine,
    ) {}

    public function execute(WhatsAppAccount $account, string $contactPhone, ?string $contactName = null): WhatsAppConversation
    {
        $lockKey = "resolve_wa_conv_{$account->workspace_id}_{$contactPhone}";
        $lock = Cache::lock($lockKey, 10);

        return $lock->block(5, function () use ($account, $contactPhone, $contactName) {
            $conversation = WhatsAppConversation::withoutGlobalScopes()
                ->where('workspace_id', $account->workspace_id)
                ->where('account_id', $account->id)
                ->where('contact_phone', $contactPhone)
                ->whereIn('status', ['open', 'pending'])
                ->first();

            if ($conversation) {
                if ($contactName && !$conversation->contact_name) {
                    $conversation->update(['contact_name' => $contactName]);
                }
                return $conversation;
            }

            $conversation = WhatsAppConversation::create([
                'uuid'          => (string) Str::uuid(),
                'workspace_id'  => $account->workspace_id,
                'account_id'    => $account->id,
                'contact_phone' => $contactPhone,
                'contact_name'  => $contactName,
                'type'          => 'general',
                'status'        => 'open',
                'priority'      => 'medium',
            ]);

            $this->slaEngine->applySla($conversation);
            $this->routingEngine->routeNewConversation($conversation);

            return $conversation;
        });
    }
}
