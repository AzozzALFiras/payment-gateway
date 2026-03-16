<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Exceptions;

/**
 * Thrown when request validation fails (missing fields, invalid data).
 */
class ValidationException extends PaymentException
{
    /** @var array<int, string> */
    protected array $validationErrors;

    /**
     * @param array<int, string> $errors
     * @param array<string, mixed> $data
     */
    public function __construct(string $message = '', array $errors = [], int $code = 0, ?\Throwable $previous = null, array $data = [])
    {
        parent::__construct($message, $code, $previous, $data);
        $this->validationErrors = $errors;
    }

    /**
     * Get the list of validation error messages.
     *
     * @return array<int, string>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
