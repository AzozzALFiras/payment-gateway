<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Exceptions;

/**
 * Thrown when the remote API returns an error response.
 */
class ApiException extends PaymentException
{
    protected int $httpStatusCode;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(string $message = '', int $httpStatusCode = 0, int $code = 0, ?\Throwable $previous = null, array $data = [])
    {
        parent::__construct($message, $code, $previous, $data);
        $this->httpStatusCode = $httpStatusCode;
    }

    /**
     * Get the HTTP status code from the API response.
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}
