<?php

namespace SmsPay\Exception;

/**
 * Represents a general API error returned by the SmsPay gateway.
 *
 * Thrown when the server responds with an unexpected HTTP status code
 * that does not fall into a more specific exception category.
 */
class ApiException extends \Exception
{
    /** @var int|null */
    protected $httpStatus;

    /** @var string|null */
    protected $httpBody;

    /** @var array|null */
    protected $jsonBody;

    /**
     * @param string      $message
     * @param int|null    $httpStatus
     * @param string|null $httpBody
     */
    public function __construct(string $message = '', ?int $httpStatus = null, ?string $httpBody = null)
    {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
        $this->httpBody   = $httpBody;
        $this->jsonBody   = $httpBody ? json_decode($httpBody, true) : null;
    }

    /**
     * @return int|null
     */
    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    /**
     * @return string|null
     */
    public function getHttpBody(): ?string
    {
        return $this->httpBody;
    }

    /**
     * @return array|null
     */
    public function getJsonBody(): ?array
    {
        return $this->jsonBody;
    }
}
