<?php

declare(strict_types=1);

namespace App\Enums;

enum ConsentType: string
{
    case PrivacyPolicy = 'privacy_policy';
    case DirectMarketingSms = 'direct_marketing_sms';
    case DirectMarketingEmail = 'direct_marketing_email';
    case CookiesTracking = 'cookies_tracking';
    case CrossBorderTransfer = 'cross_border_transfer';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}

