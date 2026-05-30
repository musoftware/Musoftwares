<?php

return [
    App\Providers\AppServiceProvider::class,
    Nwidart\Modules\LaravelModulesServiceProvider::class,
    Modules\CRM\app\Features\CRMWhatsAppInbox\CRMWhatsAppInboxServiceProvider::class,
    Modules\CRM\app\Features\CRMWhatsAppCampaigns\CRMWhatsAppCampaignsServiceProvider::class,
];
