<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Exceptions;

/**
 * Thrown when API authentication fails (invalid key, expired token, bad hash).
 */
class AuthenticationException extends PaymentException
{
}
