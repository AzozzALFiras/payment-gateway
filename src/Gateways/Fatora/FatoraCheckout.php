<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Fatora;

use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Fatora Checkout Sub-module.
 *
 * Handles standard checkout and payment verification.
 */
class FatoraCheckout
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Request a checkout URL.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->http->post($this->baseUrl . '/v1/payments/checkout', $payload);
    }

    /**
     * Verify a payment.
     *
     * @return array<string, mixed>
     */
    public function verify(string $orderId): array
    {
        return $this->http->post($this->baseUrl . '/v1/payments/verify', ['order_id' => $orderId]);
    }
}
