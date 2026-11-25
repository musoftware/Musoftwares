<?php

namespace App\Services\ClientSystems;

use App\Models\ClientSystem;
use App\Models\ClientSystemBill;
use Carbon\CarbonInterface;

/**
 * Where a client system stands right now: active, due (warning, still working) or locked.
 * The oldest unpaid bill decides; an admin lock or an "open until" date overrides it.
 */
final class SystemStatus
{
    public const ACTIVE = 'active';

    public const DUE = 'due';

    public const LOCKED = 'locked';

    private function __construct(
        public readonly string $state,
        public readonly ?ClientSystemBill $bill,
        public readonly ?CarbonInterface $changesAt,
    ) {}

    public static function of(ClientSystem $system, CarbonInterface $now): self
    {
        $bill = self::oldestOpenBill($system);

        if ($system->manually_locked) {
            return new self(self::LOCKED, $bill, null);
        }
        if ($bill === null) {
            return new self(self::ACTIVE, null, null);
        }
        if ($system->open_until?->isAfter($now)) {
            return new self(self::ACTIVE, $bill, $system->open_until);
        }
        if ($bill->lock_at->isAfter($now)) {
            return new self(self::DUE, $bill, $bill->lock_at);
        }

        return new self(self::LOCKED, $bill, null);
    }

    public static function oldestOpenBill(ClientSystem $system): ?ClientSystemBill
    {
        return $system->bills()
            ->with('invoice')
            ->whereHas('invoice', fn ($query) => $query->whereIn('status', ClientSystemBill::OPEN_INVOICE_STATUSES))
            ->orderBy('due_at')
            ->first();
    }
}
