<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Services;

/**
 * Immutable result of a price validation pass.
 */
final class ValidationResult
{
    private function __construct(
        public readonly bool    $passed,
        public readonly bool    $isDuplicate,
        public readonly bool    $isAnomaly,
        public readonly ?string $failureCode,
        public readonly ?string $failureReason,
    ) {}

    public static function valid(): self
    {
        return new self(true, false, false, null, null);
    }

    public static function invalid(string $code, string $reason): self
    {
        return new self(false, false, false, $code, $reason);
    }

    public static function anomaly(string $code, string $reason): self
    {
        return new self(false, false, true, $code, $reason);
    }

    public static function duplicate(): self
    {
        return new self(true, true, false, null, null);
    }

    public function shouldSkip(): bool
    {
        return !$this->passed || $this->isDuplicate;
    }

    public function shouldStore(): bool
    {
        return $this->passed && !$this->isDuplicate;
    }
}
