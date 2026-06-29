<?php

namespace App\Providers;

use App\Helpers\MuFcmChannel;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register the custom FCM notification channel.
     *
     * The framework's default NotificationServiceProvider remains auto-registered
     * (Laravel 11 bootstrap). We only extend the ChannelManager here.
     */
    public function boot(): void
    {
        // Extend the channel manager once (and every time) it resolves so the
        // 'fcm' driver is available regardless of resolution order.
        $this->app->afterResolving(ChannelManager::class, function (ChannelManager $manager): void {
            $this->extendFcmChannel($manager);
        });

        // If the manager is already resolved (e.g. used early), extend now.
        if ($this->app->resolved(ChannelManager::class)) {
            $this->extendFcmChannel($this->app->make(ChannelManager::class));
        }
    }

    protected function extendFcmChannel(ChannelManager $manager): void
    {
        // Avoid double-extension if boot runs more than once.
        try {
            if ($manager->channel('fcm') instanceof MuFcmChannel) {
                return;
            }
        } catch (\Throwable $e) {
            // channel() throws when the driver is not registered yet — proceed.
        }

        $manager->extend('fcm', function () {
            return $this->app->make(MuFcmChannel::class);
        });
    }
}
