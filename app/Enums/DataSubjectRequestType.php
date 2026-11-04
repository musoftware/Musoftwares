<?php

declare(strict_types=1);

namespace App\Enums;

enum DataSubjectRequestType: string
{
    case AccessData = 'access_data';
    case UpdateData = 'update_data';
    case DeleteAccount = 'delete_account';
    case RestrictProcessing = 'restrict_processing';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}

