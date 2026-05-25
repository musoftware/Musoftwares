<?php

namespace App\Exceptions;

use Exception;

class SaaSLimitExceededException extends Exception
{
    protected $usageKey;

    public function __construct(string $usageKey, string $message = "")
    {
        $this->usageKey = $usageKey;
        if (empty($message)) {
            $message = "SaaS limit exceeded for feature: {$usageKey}";
        }
        parent::__construct($message);
    }

    public function getUsageKey(): string
    {
        return $this->usageKey;
    }
}
