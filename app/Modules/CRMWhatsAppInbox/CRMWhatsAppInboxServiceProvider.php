<?php

namespace App\Modules\CRMWhatsAppInbox;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Console\Scheduling\Schedule;
use App\Modules\CRMWhatsAppInbox\Contracts\WhatsAppProviderInterface;
use App\Modules\CRMWhatsAppInbox\Events;
use App\Modules\CRMWhatsAppInbox\Listeners;

class CRMWhatsAppInboxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the WhatsApp provider interface — swap implementation here
        $this->app->bind(WhatsAppProviderInterface::class, function ($app) {
            // Default: null driver (no-op) for development
            // Replace with actual provider implementation
            return new class implements WhatsAppProviderInterface {
                public function connect(\Modules\CRM\Models\WhatsAppAccount $account): array { return ['status' => 'connecting']; }
                public function disconnect(\Modules\CRM\Models\WhatsAppAccount $account): bool { return true; }
                public function getStatus(\Modules\CRM\Models\WhatsAppAccount $account): string { return $account->status; }
                public function sendText(\Modules\CRM\Models\WhatsAppAccount $account, string $to, string $body): array { return ['message_id' => uniqid('msg_'), 'status' => 'sent']; }
                public function sendMedia(\Modules\CRM\Models\WhatsAppAccount $account, string $to, string $mediaUrl, string $type, ?string $caption = null): array { return ['message_id' => uniqid('msg_'), 'status' => 'sent']; }
                public function sendTemplate(\Modules\CRM\Models\WhatsAppAccount $account, string $to, string $templateName, array $params = []): array { return ['message_id' => uniqid('msg_'), 'status' => 'sent']; }
                public function getQrCode(\Modules\CRM\Models\WhatsAppAccount $account): ?string { return null; }
                public function getDeviceInfo(\Modules\CRM\Models\WhatsAppAccount $account): array { return []; }
            };
        });

        // Register services as singletons
        $this->app->singleton(Services\WhatsAppInboxService::class);
        $this->app->singleton(Services\ConversationAssignmentEngine::class);
        $this->app->singleton(Services\MessageDeliveryService::class);
        $this->app->singleton(Services\WhatsAppSessionManager::class);
        $this->app->singleton(Services\ConversationRoutingEngine::class);
        $this->app->singleton(Services\CRMWhatsAppLimitsService::class);
        $this->app->singleton(Services\WhatsAppRealtimeBroadcaster::class);
        $this->app->singleton(Services\WhatsAppAutomationEngine::class);
        $this->app->singleton(Services\WhatsAppSlaEngine::class);
        $this->app->singleton(Services\WhatsAppAnalyticsService::class);
        $this->app->singleton(Services\WhatsAppMediaService::class);
    }

    public function boot(): void
    {
        $this->registerEventListeners();
        $this->registerCommands();
        $this->registerSchedule();
    }

    protected function registerEventListeners(): void
    {
        // WhatsAppMessageReceived
        Event::listen(Events\WhatsAppMessageReceived::class, [
            Listeners\UpdateCrmTimelineOnMessage::class,
            Listeners\BroadcastRealtimeUpdate::class,
            Listeners\MatchLeadOnNewConversation::class,
            Listeners\LogMessageActivity::class,
            Listeners\EvaluateAutomationsOnEvent::class,
            Listeners\NotifyAssignedAgent::class,
            Listeners\DispatchWhatsAppWebhooks::class,
            Listeners\UpdateConversationMetrics::class,
        ]);

        // WhatsAppMessageSent
        Event::listen(Events\WhatsAppMessageSent::class, [
            Listeners\UpdateCrmTimelineOnMessage::class,
            Listeners\BroadcastRealtimeUpdate::class,
            Listeners\LogMessageActivity::class,
            Listeners\DispatchWhatsAppWebhooks::class,
            Listeners\UpdateConversationMetrics::class,
        ]);

        // WhatsAppConversationAssigned
        Event::listen(Events\WhatsAppConversationAssigned::class, [
            Listeners\BroadcastRealtimeUpdate::class,
            Listeners\NotifyAssignedAgent::class,
            Listeners\LogMessageActivity::class,
            Listeners\DispatchWhatsAppWebhooks::class,
        ]);

        // WhatsAppConversationResolved
        Event::listen(Events\WhatsAppConversationResolved::class, [
            Listeners\BroadcastRealtimeUpdate::class,
            Listeners\LogMessageActivity::class,
            Listeners\DispatchWhatsAppWebhooks::class,
        ]);

        // WhatsAppAccountConnected
        Event::listen(Events\WhatsAppAccountConnected::class, [
            Listeners\LogMessageActivity::class,
            Listeners\DispatchWhatsAppWebhooks::class,
        ]);

        // WhatsAppMessageFailed
        Event::listen(Events\WhatsAppMessageFailed::class, [
            Listeners\LogMessageActivity::class,
            Listeners\NotifyAssignedAgent::class,
            Listeners\DispatchWhatsAppWebhooks::class,
        ]);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\CheckSlaBreachesCommand::class,
                Console\ResetMonthlyMessageUsageCommand::class,
            ]);
        }
    }

    protected function registerSchedule(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Check SLA breaches every minute
            $schedule->command('crm:whatsapp:check-sla')->everyMinute();

            // Reset monthly usage on the 1st of each month
            $schedule->command('crm:whatsapp:reset-usage')->monthlyOn(1, '00:00');

            // Sync session health every 5 minutes
            $schedule->job(new Jobs\SyncWhatsAppSessionJob)->everyFiveMinutes();

            // Recover failed deliveries every 15 minutes
            $schedule->job(new Jobs\RecoverFailedDeliveriesJob)->everyFifteenMinutes();
        });
    }
}
