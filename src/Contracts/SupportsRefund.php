<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Contracts;

use AzozzALFiras\PaymentGateway\DTOs\RefundRequest;
use AzozzALFiras\PaymentGateway\DTOs\RefundResponse;

/**
 * Gateways implementing this interface support refund operations.
 */
interface SupportsRefund
{
    /**
     * Process a full refund.
     */
    public function refund(RefundRequest $request): RefundResponse;

    /**
     * Process a partial refund.
     */
    public function partialRefund(RefundRequest $request): RefundResponse;
}
