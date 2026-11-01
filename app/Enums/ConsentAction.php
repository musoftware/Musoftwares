<?php

declare(strict_types=1);

namespace App\Enums;

enum ConsentAction: string
{
    case Granted = 'granted';
    case Withdrawn = 'withdrawn';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}

