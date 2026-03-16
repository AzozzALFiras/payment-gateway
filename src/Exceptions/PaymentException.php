<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Exceptions;

/**
 * Base exception for all payment gateway errors.
 */
class PaymentException extends \RuntimeException
{
    /** @var array<string, mixed> */
    protected array $errorData = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, array $data = [])
    {
        parent::__construct($message, $code, $previous);
        $this->errorData = $data;
    }

    /**
     * Get additional error data from the gateway response.
     *
     * @return array<string, mixed>
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }
}
