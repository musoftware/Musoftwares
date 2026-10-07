<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when no exchange rate exists for a currency pair.
 * Money-moving code must let this fail; display-only code may catch it and show a fallback.
 */
class MissingExchangeRateException extends RuntimeException
{
    public function __construct(
        public readonly string $fromCurrencyId,
        public readonly string $toCurrencyId,
        public readonly ?string $date = null,
    ) {
        parent::__construct(sprintf(
            'No exchange rate found from currency %s to currency %s%s.',
            $fromCurrencyId,
            $toCurrencyId,
            $date ? " for {$date}" : ''
        ));
    }
}
