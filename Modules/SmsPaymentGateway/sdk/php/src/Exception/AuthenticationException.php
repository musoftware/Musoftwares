<?php

namespace SmsPay\Exception;

/**
 * Thrown when the API key is missing, invalid, or revoked (HTTP 401).
 */
class AuthenticationException extends ApiException
{
    /**
     * @param string      $message
     * @param string|null $httpBody
     */
    public function __construct(string $message = '', ?string $httpBody = null)
    {
        parent::__construct($message, 401, $httpBody);
    }
}
