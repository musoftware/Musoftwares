<?php

namespace SmsPay\Exception;

/**
 * Thrown when the request is malformed or contains invalid parameters (HTTP 400/422).
 *
 * Check {@see getJsonBody()} for structured validation error details.
 */
class InvalidRequestException extends ApiException
{
    /** @var string|null The specific parameter that caused the error, if available. */
    protected $errorParam;

    /**
     * @param string      $message
     * @param string|null $errorParam
     * @param int         $httpStatus
     * @param string|null $httpBody
     */
    public function __construct(
        string $message = '',
        ?string $errorParam = null,
        int $httpStatus = 400,
        ?string $httpBody = null
    ) {
        parent::__construct($message, $httpStatus, $httpBody);
        $this->errorParam = $errorParam;
    }

    /**
     * @return string|null
     */
    public function getErrorParam(): ?string
    {
        return $this->errorParam;
    }
}
