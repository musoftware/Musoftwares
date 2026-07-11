<?php

namespace App\Enums;

enum GuestTicketStatus: string
{
    case Pending = 'pending';
    case Replied = 'replied';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('general.status_pending'),
            self::Replied => __('general.status_replied'),
            self::Closed  => __('general.status_closed'),
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending => in_array($next, [self::Replied, self::Closed], true),
            self::Replied => in_array($next, [self::Closed, self::Replied], true),
            self::Closed  => in_array($next, [self::Replied], true),
        };
    }
}
