<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Exceptions;

use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;
use RuntimeException;

class GoldProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly GoldMarketSource $source,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
