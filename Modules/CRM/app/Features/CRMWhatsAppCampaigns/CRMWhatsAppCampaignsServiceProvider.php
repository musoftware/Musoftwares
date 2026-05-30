<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Console\Scheduling\Schedule;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Listeners;

class CRMWhatsAppCampaignsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(Services\WhatsAppCampaignService::class);
        $this->app->singleton(Services\CampaignAudienceResolver::class);
        $this->app->singleton(Services\CampaignSequenceEngine::class);
        $this->app->singleton(Services\CampaignDeliveryManager::class);
        $this->app->singleton(Services\WhatsAppTemplateRenderer::class);
        $this->app->singleton(Services\CRMWhatsAppCampaignLimitsService::class);
        $this->app->singleton(Services\CampaignAnalyticsAggregator::class);
    }

    public function boot(): void
    {
        $this->registerEventListeners();
        $this->registerCommands();
        $this->registerSchedule();
    }

    protected function registerEventListeners(): void
    {
        // WhatsAppCampaignCreated
        Event::listen(Events\WhatsAppCampaignCreated::class, [
            Listeners\LogCampaignActivity::class,
            Listeners\DispatchCampaignWebhooks::class,
        ]);

        // WhatsAppCampaignStarted
        Event::listen(Events\WhatsAppCampaignStarted::class, [
            Listeners\BroadcastCampaignProgress::class,
            Listeners\LogCampaignActivity::class,
            Listeners\NotifyCampaignManagers::class,
            Listeners\DispatchCampaignWebhooks::class,
        ]);

        // WhatsAppCampaignCompleted
        Event::listen(Events\WhatsAppCampaignCompleted::class, [
            Listeners\BroadcastCampaignProgress::class,
            Listeners\LogCampaignActivity::class,
            Listeners\NotifyCampaignManagers::class,
            Listeners\DispatchCampaignWebhooks::class,
        ]);

        // WhatsAppCampaignFailed
        Event::listen(Events\WhatsAppCampaignFailed::class, [
            Listeners\BroadcastCampaignProgress::class,
            Listeners\LogCampaignActivity::class,
            Listeners\NotifyCampaignManagers::class,
            Listeners\DispatchCampaignWebhooks::class,
        ]);

        // WhatsAppCampaignMessageDelivered
        Event::listen(Events\WhatsAppCampaignMessageDelivered::class, [
            Listeners\BroadcastCampaignProgress::class,
            Listeners\UpdateCrmTimelineOnCampaign::class,
            Listeners\UpdateCampaignAnalytics::class,
            Listeners\DispatchCampaignWebhooks::class,
        ]);

        // WhatsAppCampaignSequenceTriggered
        Event::listen(Events\WhatsAppCampaignSequenceTriggered::class, [
            Listeners\TriggerSequenceOnEvent::class,
            Listeners\LogCampaignActivity::class,
            Listeners\DispatchCampaignWebhooks::class,
        ]);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ProcessScheduledCampaignsCommand::class,
                Console\AggregateCampaignAnalyticsCommand::class,
                Console\RecoverFailedCampaignDeliveriesCommand::class,
            ]);
        }
    }

    protected function registerSchedule(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Process scheduled campaigns every minute
            $schedule->command('crm:wa-campaigns:process-scheduled')->everyMinute();

            // Aggregate analytics every 10 minutes
            $schedule->command('crm:wa-campaigns:aggregate-analytics')->everyTenMinutes();

            // Recover failed deliveries every 15 minutes
            $schedule->command('crm:wa-campaigns:recover-failed')->everyFifteenMinutes();
        });
    }
}
