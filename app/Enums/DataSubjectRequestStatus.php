<?php

declare(strict_types=1);

namespace App\Enums;

enum DataSubjectRequestStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case RejectedWithReason = 'rejected_with_reason';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}

