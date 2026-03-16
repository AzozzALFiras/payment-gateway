<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Contracts;

use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;

/**
 * Gateways implementing this interface support recurring payments.
 */
interface SupportsRecurring
{
    /**
     * Create a recurring payment.
     */
    public function recurring(PaymentRequest $request): PaymentResponse;
}
