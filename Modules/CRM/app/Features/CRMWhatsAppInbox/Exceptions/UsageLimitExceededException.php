<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Exceptions;

class UsageLimitExceededException extends \RuntimeException
{
    public function __construct(string $message = 'Usage limit exceeded.', int $code = 429, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
