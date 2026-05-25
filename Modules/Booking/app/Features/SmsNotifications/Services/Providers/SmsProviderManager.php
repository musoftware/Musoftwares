<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services\Providers;

use InvalidArgumentException;

class SmsProviderManager
{
    public function resolve(string $providerName): SmsProviderInterface
    {
        return match (strtolower($providerName)) {
            'smsmisr' => new SmsMisrProvider(),
            'twilio' => new TwilioProvider(),
            'smscountry' => new SmsCountryProvider(),
            'futuresms' => new FutureSmsProvider(),
            'portalfuturesms' => new PortalFutureSmsProvider(),
            'masrbokra' => new MasrBokraProvider(),
            'infobip', 'am_info' => new InfoBipProvider(),
            'smswe' => new SmsWeBusinessProvider(),
            'smseg' => new SmsSmartEgyptProvider(),
            'broadnet' => new BroadnetSmsProvider(),
            'whysms' => new WhySmsProvider(),
            default => throw new InvalidArgumentException("SMS Provider [{$providerName}] is not supported."),
        };
    }
}
