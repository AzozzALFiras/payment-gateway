<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Contracts;

use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;

/**
 * Base contract that all payment gateway drivers must implement.
 */
interface GatewayInterface
{
    /**
     * Get the gateway display name.
     */
    public function getName(): string;

    /**
     * Check if the gateway is in test/sandbox mode.
     */
    public function isTestMode(): bool;

    /**
     * Initiate a payment / purchase.
     *
     * @param PaymentRequest $request
     * @return PaymentResponse
     */
    public function purchase(PaymentRequest $request): PaymentResponse;

    /**
     * Get the status of a payment by its identifier.
     *
     * @param string $paymentId
     * @return PaymentResponse
     */
    public function status(string $paymentId): PaymentResponse;
}
